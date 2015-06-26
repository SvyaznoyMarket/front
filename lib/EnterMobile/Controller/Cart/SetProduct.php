<?php

namespace EnterMobile\Controller\Cart;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\RouterTrait;
use EnterMobile\Routing;
use EnterMobile\Controller;
use EnterQuery as Query;
use EnterMobile\Model;
//use EnterMobile\Model\JsonPage as Page;
use EnterMobile\Repository;

class SetProduct {
    use ConfigTrait, RouterTrait, LoggerTrait, CurlTrait, SessionTrait;

    /**
     * @param Http\Request $request
     * @return Http\Response
     * @throws \Exception
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $session = $this->getSession();
        $cartRepository = new \EnterRepository\Cart();

        try {
            $productId = (new \EnterRepository\Product())->getIdByHttpRequest($request);
            if (!$productId) {
                throw new \Exception(sprintf('Товар #%s не найден', $productId));
            }
            $quantity = (int)$request->query['quantity'];
            if ($quantity <= 0) {
                $quantity = 1;
            }

            // корзина из сессии
            $cart = $cartRepository->getObjectByHttpSession($session, $config->cart->sessionKey);

            $cartProduct = new \EnterModel\Cart\Product();
            $cartProduct->id = $productId;
            $cartProduct->quantity = $quantity;

            // добавление товара в корзину
            $cartRepository->setProductForObject($cart, $cartProduct);

            // сохранение корзины в сессию
            $cartRepository->saveObjectToHttpSession($session, $cart, $config->cart->sessionKey);

            // удалить разбиение заказа
            $session->remove($config->order->splitSessionKey);
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['cart']]);
        }

        return (new \EnterAggregator\Controller\Redirect())->execute($request->server['HTTP_REFERER'] ?: $this->getRouter()->getUrlByRoute(new Routing\Index()), 302);
    }
}