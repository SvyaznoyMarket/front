<?php

namespace EnterMobile\Controller\Order\Payment;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\DebugContainerTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterModel as Model;
use EnterQuery as Query;
use EnterMobile\Routing;
use EnterMobile\Controller;
use EnterMobile\Repository;

class Start {
    use ConfigTrait, CurlTrait, SessionTrait, RouterTrait, LoggerTrait, MustacheRendererTrait, DebugContainerTrait;

    /**
     * @param Http\Request $request
     * @return Http\Response
     * @throws \Exception
     */
    public function execute(Http\Request $request) {
        try {
            $config = $this->getConfig();
            $curl = $this->getCurl();
            $session = $this->getSession();

            $orderAccessToken = $request->data['orderAccessToken'] ? (string)$request->data['orderAccessToken'] : null;
            if (!$orderAccessToken) {
                throw new \Exception('Не передан идентификатор заказа', Http\Response::STATUS_BAD_REQUEST);
            }

            $paymentMethodId = $request->data['paymentMethodId'] ? (string)$request->data['paymentMethodId'] : null;
            if (!$paymentMethodId) {
                throw new \Exception('Не передан идентификатор метода оплаты', Http\Response::STATUS_BAD_REQUEST);
            }

            $userItemQuery = null;
            if ($userToken = (new \EnterMobile\Repository\User())->getTokenBySessionAndHttpRequest($session, $request)) {
                $userItemQuery = new Query\User\GetItemByToken($userToken);
                $curl->prepare($userItemQuery);
            }

            $orderQuery = new Query\Order\GetItemByAccessToken($orderAccessToken);
            $curl->prepare($orderQuery);

            $curl->execute();

            // пользователь
            $user = null;
            try {
                if ($userItemQuery) {
                    $user = (new \EnterRepository\User())->getObjectByQuery($userItemQuery);
                }
            } catch (\Exception $e) {
                $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller']]);
            }

            if (!$user) {
                $user = new Model\User();
            }

            $order = (new \EnterRepository\Order())->getObjectByQuery($orderQuery);
            if (!$order) {
                throw new \Exception('Не удалось получить заказ', Http\Response::STATUS_INTERNAL_SERVER_ERROR);
            }

            $paymentConfigQuery = new Query\Payment\GetConfig($paymentMethodId, $order->id, [
                'email'    => $user ? $user->email : null,
                'from'     => $config->hostname,
            ]+(
            !empty($request->data['orderAction'])
                ? ['action_alias' => $request->data['orderAction']]
                : []
            ));
            $paymentConfigQuery->setTimeout(30);
            $curl->prepare($paymentConfigQuery);
            $curl->execute();

            $paymentConfigResult = $paymentConfigQuery->getResult();

            if (!$paymentConfigResult) {
                throw new \Exception('Ошибка получения данных payment-config');
            }

            $paymentValidateQuery = new Query\Payment\CheckOrder($paymentConfigResult['detail']);
            $paymentValidateQuery->setTimeout(8 * $config->corePrivateService->timeout);
            $curl->prepare($paymentValidateQuery);
            $curl->execute();

            $paymentValidateResult = $paymentValidateQuery->getResult();

            if (!$paymentValidateResult) {
                throw new \Exception('Ошибка получения данных payment/robokassa-check-order');
            }
            return new Http\JsonResponse(['success' => (bool)$paymentValidateResult['success']]);
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller']]);
            return new Http\JsonResponse(['success' => false]);
        }
    }
}