<?php

namespace EnterMobile\Controller\User;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\RouterTrait;
use EnterMobile\Controller;
use EnterMobile\Repository;
use EnterQuery as Query;
use EnterMobile\Model;
use EnterMobile\Model\Form;
use EnterMobile\Routing;

class Logout {
    use ConfigTrait, CurlTrait, SessionTrait, RouterTrait;

    /**
     * @param Http\Request $request
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $router = $this->getRouter();
        $session = $this->getSession();
        $cartRepository = new \EnterRepository\Cart();

        // редирект
        $redirectUrl = !empty($request->query['redirect_to']) ? $request->query['redirect_to'] : null;
        if (!$redirectUrl) {
            $redirectUrl = $router->getUrlByRoute(new Routing\Index());
        }
        // http-ответ
        $response = (new \EnterAggregator\Controller\Redirect())->execute($redirectUrl, 302);
        // сброс cookie
        (new \EnterMobile\Repository\User())->setTokenToSessionAndHttpResponse(null, $session, $response);

        // удаление данных пользователя для ОЗ
        $session->remove($config->order->userSessionKey);

        // удаление товаров из корзину
        $cart = $cartRepository->getObjectByHttpSession($session, $config->cart->sessionKey);
        $cart->product = [];
        $cartRepository->saveObjectToHttpSession($session, $cart, $config->cart->sessionKey);

        // удалить разбиение заказа
        $session->remove($config->order->splitSessionKey);

        return $response;
    }
}