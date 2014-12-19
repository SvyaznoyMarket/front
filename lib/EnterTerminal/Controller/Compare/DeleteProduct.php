<?php

namespace EnterTerminal\Controller\Compare;

use Enter\Http;
use EnterTerminal\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\SessionTrait;
use EnterQuery as Query;
use EnterTerminal\Controller;

class DeleteProduct {
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

        // ид региона
        $regionId = (new \EnterTerminal\Repository\Region())->getIdByHttpRequest($request);
        if (!$regionId) {
            throw new \Exception('Не передан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
        }

        // сравнение из сессии
        $compare = $compareRepository->getObjectByHttpSession($session);

        // товара для сравнения
        $compareProduct = $compareRepository->getProductObjectByHttpRequest($request);
        if (!$compareProduct) {
            throw new \Exception('Товар не получен', Http\Response::STATUS_BAD_REQUEST);
        }

        // добавление товара к сравнению
        $compareRepository->deleteProductForObject($compare, $compareProduct);

        $productItemQuery = new Query\Product\GetItemById($compareProduct->id, $regionId);
        $curl->prepare($productItemQuery);

        $curl->execute();

        // сохранение сравнения в сессию
        $compareRepository->saveObjectToHttpSession($session, $compare);

        // response
        return (new Controller\Compare())->execute($request);
    }
}