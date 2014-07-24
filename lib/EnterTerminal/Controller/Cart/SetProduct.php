<?php

namespace EnterTerminal\Controller\Cart;

use Enter\Http;
use EnterTerminal\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\SessionTrait;
use EnterCurlQuery as Query;
use EnterModel as Model;
use EnterTerminal\Controller;

class SetProduct {
    use ConfigTrait, LoggerTrait, CurlTrait, SessionTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();
        $session = $this->getSession();
        $cartRepository = new \EnterRepository\Cart();

        // корзина из сессии
        $cart = $cartRepository->getObjectByHttpSession($session);

        // товара для корзины
        $cartProduct = $cartRepository->getProductObjectByHttpRequest($request);
        if (!$cartProduct) {
            throw new \Exception('Товар не получен');
        }

        // добавление товара в корзину
        $cartRepository->setProductForObject($cart, $cartProduct);

        // ид магазина
        $shopId = (new \EnterTerminal\Repository\Shop())->getIdByHttpRequest($request); // FIXME

        // запрос магазина
        $shopItemQuery = new Query\Shop\GetItemById($shopId);
        $curl->prepare($shopItemQuery);

        $curl->execute();

        // магазин
        $shop = (new \EnterRepository\Shop())->getObjectByQuery($shopItemQuery);
        if (!$shop) {
            throw new \Exception(sprintf('Магазин #%s не найден', $shopId));
        }

        $productItemQuery = new Query\Product\GetItemById($cartProduct->id, $shop->regionId);
        $curl->prepare($productItemQuery);

        // запрос корзины
        $cartItemQuery = new Query\Cart\GetItem($cart, $shop->regionId);
        $curl->prepare($cartItemQuery);

        $curl->execute();

        // корзина из ядра
        $cart = $cartRepository->getObjectByQuery($cartItemQuery);

        // сохранение корзины в сессию
        $cartRepository->saveObjectToHttpSession($session, $cart);

        // response
        return (new Controller\Cart())->execute($request);
    }
}