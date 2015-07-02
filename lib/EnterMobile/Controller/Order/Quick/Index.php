<?php

namespace EnterMobile\Controller\Order\Quick;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\RouterTrait;
use EnterMobile\Controller;
use EnterMobile\Routing;
use EnterMobile\Repository;

class Index {
    use ConfigTrait, LoggerTrait, SessionTrait, RouterTrait;

    /**
     * @param Http\Request $request
     * @return Http\Response
     * @throws \Exception
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $session = $this->getSession();
        $router = $this->getRouter();

        $cartProduct = (new \EnterRepository\Cart())->getProductObjectByHttpRequest($request);
        if (!$cartProduct) {
            throw new \Exception('Не передан товар');
        }
        if ($cartProduct->quantity <= 0) {
            throw new \Exception('Количество товара должно быть большим нуля');
        }

        $shopId = is_scalar($request->query['shopId']) ? $request->query['shopId'] : null;
        if (!$shopId) {
            throw new \Exception('Не передан ид магазина');
        }

        $cartData = [
            'product' => [
                $cartProduct->id => [
                    'id'       => $cartProduct->id,
                    'quantity' => $cartProduct->quantity,
                ],
            ],
            'shopId'  => $shopId,
        ];

        $session->set($config->cart->quickSessionKey, $cartData);
        $session->remove($config->order->splitSessionKey);

        return (new \EnterAggregator\Controller\Redirect())->execute($router->getUrlByRoute(new Routing\Order\Index(), ['shopId' => $shopId]), 302);
    }
}