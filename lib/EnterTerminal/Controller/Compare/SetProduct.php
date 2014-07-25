<?php

namespace EnterTerminal\Controller\Compare;

use Enter\Http;
use EnterTerminal\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\SessionTrait;
use EnterCurlQuery as Query;
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
        $compareRepository = new \EnterRepository\Compare();

        // сравнение из сессии
        $compare = $compareRepository->getObjectByHttpSession($session);

        // товара для сравнения
        $compareProduct = $compareRepository->getProductObjectByHttpRequest($request);
        if (!$compareProduct) {
            throw new \Exception('Товар не получен');
        }

        // добавление товара к сравнению
        $compareRepository->setProductForObject($compare, $compareProduct);

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

        $productItemQuery = new Query\Product\GetItemById($compareProduct->id, $shop->regionId);
        $curl->prepare($productItemQuery);

        $curl->execute();

        // сохранение сравнения в сессию
        $compareRepository->saveObjectToHttpSession($session, $compare);

        // response
        return (new Controller\Compare())->execute($request);
    }
}