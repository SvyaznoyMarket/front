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

class GetForm {
    use ConfigTrait, CurlTrait, SessionTrait, RouterTrait, LoggerTrait, MustacheRendererTrait, DebugContainerTrait;

    /**
     * @param Http\Request $request
     * @return Http\Response
     * @throws \Exception
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();
        $router = $this->getRouter();
        $session = $this->getSession();
        $paymentRepository = new \EnterRepository\Payment();

        $orderId = $request->data['orderId'] ? (string)$request->data['orderId'] : null;
        if (!$orderId) {
            throw new \Exception('Не передан идентификатор заказа', Http\Response::STATUS_BAD_REQUEST);
        }

        $paymentMethodId = $request->data['methodId'] ? (string)$request->data['methodId'] : null;
        if (!$orderId) {
            throw new \Exception('Не передан идентификатор метода оплаты', Http\Response::STATUS_BAD_REQUEST);
        }

        // запрос пользователя
        $userItemQuery = null;
        if ($userToken = (new \EnterRepository\User())->getTokenByHttpRequest($request)) {
            $userItemQuery = new Query\User\GetItemByToken($userToken);
            $curl->prepare($userItemQuery);
        }

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

        $data = [
            'back_ref' => $router->getUrlByRoute(new Routing\Order\Complete()),
            'email'    => $user ? $user->email : null,
        ];

        $paymentConfigQuery = new Query\Payment\GetConfig($paymentMethodId, $orderId, $data);
        $paymentConfigQuery->setTimeout(8 * $config->corePrivateService->timeout);
        $curl->prepare($paymentConfigQuery);

        $curl->execute();

        $renderer = $this->getRenderer();

        $formContent = null;
        if ('5' === $paymentMethodId) {
            $form = $paymentRepository->getPsbFormByQuery($paymentConfigQuery);
            $formContent = $renderer->render('partial/payment/psb-form', ['form' => $form]);
        } else if ('8' === $paymentMethodId) {
            $form = $paymentRepository->getPsbInvoiceFormByQuery($paymentConfigQuery);
            $formContent = $renderer->render('partial/payment/psbInvoice-form', ['form' => $form]);
        }

        // http-ответ
        $response = new Http\JsonResponse([
            'form' => $formContent,
        ]);

        return $response;
    }
}