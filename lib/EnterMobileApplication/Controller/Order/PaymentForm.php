<?php

namespace EnterMobileApplication\Controller\Order {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterRepository as Repository;
    use EnterMobileApplication\Controller\Order\PaymentForm\Response;

    class PaymentForm
    {
        use ConfigTrait, LoggerTrait, CurlTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request)
        {
            $config = $this->getConfig();
            $curl = $this->getCurl();
            $cartRepository = new Repository\Cart();

            // ид региона
            $regionId = is_string($request->query['regionId']) ? $request->query['regionId'] : null;
            if (!$regionId) {
                throw new \Exception('Не передан regionId', Http\Response::STATUS_BAD_REQUEST);
            }
            // токен для получения заказа
            $accessToken = is_string($request->query['accessToken']) ? $request->query['accessToken'] : null;
            if (!$accessToken) {
                throw new \Exception('Не передан accessToken', Http\Response::STATUS_BAD_REQUEST);
            }
            // ид метода оплаты
            $paymentMethodId = is_string($request->query['paymentMethodId']) ? $request->query['paymentMethodId'] : null;
            if (!$paymentMethodId) {
                throw new \Exception('Не передан paymentMethodId', Http\Response::STATUS_BAD_REQUEST);
            }
            // success url
            $successUrl = is_string($request->query['successUrl']) ? $request->query['successUrl'] : null;
            if (!$successUrl) {
                throw new \Exception('Не передан successUrl', Http\Response::STATUS_BAD_REQUEST);
            }
            // fail url
            $failUrl = is_string($request->query['failUrl']) ? $request->query['failUrl'] : null;
            if (!$failUrl) {
                throw new \Exception('Не передан failUrl', Http\Response::STATUS_BAD_REQUEST);
            }
            // токен пользователя
            $token = is_scalar($request->query['token']) ? (string)$request->query['token'] : null;

            // запрос заказа
            $itemQuery = new Query\Order\GetItemByAccessToken($accessToken);
            $itemQuery->setTimeout(3 * $config->coreService->timeout);
            $curl->prepare($itemQuery);

            // запрос пользователя
            $userItemQuery = null;
            if ($token && (0 !== strpos($token, 'anonymous-'))) {
                $userItemQuery = new Query\User\GetItemByToken($token);
                $curl->prepare($userItemQuery);
            }

            $curl->execute();

            // заказ
            $order = (new Repository\Order())->getObjectByQuery($itemQuery);
            if (!$order) {
                throw new \Exception('Заказ не найден', Http\Response::STATUS_NOT_FOUND);
            }

            $cart = call_user_func(function() use (&$order, &$cartRepository) {
                $return = new Model\Cart();
                foreach ($order->product as $orderProduct) {
                    $cartRepository->setProductForObject($return, new Model\Cart\Product(['id' => $orderProduct->id, 'quantity' => $orderProduct->quantity]));
                }

                return $return;
            });

            $paymentGroupQuery = new Query\PaymentGroup\GetList($regionId, $cart);
            $curl->prepare($paymentGroupQuery);

            // пользователь
            $user = null;
            try {
                if ($userItemQuery) {
                    $user = (new Repository\User())->getObjectByQuery($userItemQuery);
                }
            } catch (\Exception $e) {
                $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller']]);
            }

            $curl->execute();

            $action = call_user_func(function() use (&$paymentGroupQuery, &$order) {
                $data = [];
                try {
                    $data = $paymentGroupQuery->getResult();
                } catch (\Exception $e) {
                    $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['repository']]);
                }

                foreach ($data as $item) {
                    if (!isset($item['payment_methods'][0])) continue;

                    foreach ($item['payment_methods'] as $methodItem) {
                        if (
                            ((string)$methodItem['id'] !== $order->paymentMethodId)
                            || empty($methodItem['available_actions'])
                            || !is_array($methodItem['available_actions'])
                        ) {
                            continue;
                        }

                        return reset($methodItem['available_actions']);
                    }
                }

                return null;
            });

            $paymentConfigQuery = new Query\Payment\GetConfig(
                $paymentMethodId,
                $order->id,
                [
                    'back_ref' => $successUrl,
                    'fail_ref' => $failUrl,
                    'email'    => false && $user ? $user->email : null,
                ]
                + (
                    !empty($action['alias'])
                    ? ['action_alias' => $action['alias']]
                    : []
                )
            );
            $paymentConfigQuery->setTimeout(8 * $config->corePrivateService->timeout);
            $curl->prepare($paymentConfigQuery);

            $curl->execute();

            $paymentConfigResult = $paymentConfigQuery->getResult();
            if (!isset($paymentConfigResult['url'])) {
                throw new \Exception('Не получен url оплаты');
            }
            if (!isset($paymentConfigResult['detail'])) {
                throw new \Exception('Не получена форма оплаты');
            }

            // фикс для core.response
            if (isset($paymentConfigResult['detail']['url'])) {
                unset($paymentConfigResult['detail']['url']);
            }

            // ответ
            $response = new Response();
            $form = $response->createForm();
            $form->url = $paymentConfigResult['url'];
            foreach ($paymentConfigResult['detail'] as $key => $value) {
                if (null === $value) continue;

                $form->fields[] = [
                    'name'  => $key,
                    'value' => $value,
                ];
            }

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterMobileApplication\Controller\Order\PaymentForm {
    use EnterModel as Model;

    class Response {
        /** @var array */
        public $form = [];

        public function createForm() {
            $this->form = new Response\Form();

            return $this->form;
        }
    }
}

namespace EnterMobileApplication\Controller\Order\PaymentForm\Response {
    class Form {
        /** @var string */
        public $url;
        /** @var array */
        public $fields = [];
    }
}