<?php

namespace EnterMobile\Controller\Order\Quick;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterMobile\Controller;
use EnterAggregator\SessionTrait;
use EnterMobile\Repository;

class Index {
    use ConfigTrait, LoggerTrait, SessionTrait;

    /**
     * @param Http\Request $request
     * @return Http\Response
     * @throws \Exception
     */
    public function execute(Http\Request $request) {
        $session = $this->getSession();

        $cartProduct = (new \EnterRepository\Cart())->getProductObjectByHttpRequest($request);
        if (!$cartProduct) {
            throw new \Exception('Не передан товар');
        }
        if ($cartProduct->quantity <= 0) {
            throw new \Exception('Количество товара должно быть большим нуля');
        }

        // FIXME: заглушка
        $sessionKey = 'user/cart/one-click';

        $cartData = [
            'product' => [
                $cartProduct->id => [
                    'quantity' => $cartProduct->quantity,
                ],
            ],
        ];

        $session->set($sessionKey, $cartData);

        $url = strtr($request->getSchemeAndHttpHost(), [
            'm.'    => '',
            ':8080' => '', //FIXME: костыль для nginx-а
        ]) . '/orders/one-click/new';
        if ($request->query['shopId']) {
            $url .= (false === strpos($url, '?') ? '?' : '&') . http_build_query(['shopId' => $request->query['shopId']]);
        }

        return (new \EnterAggregator\Controller\Redirect())->execute($url, 302);
    }
}