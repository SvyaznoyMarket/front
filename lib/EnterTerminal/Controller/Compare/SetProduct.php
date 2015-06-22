<?php

namespace EnterTerminal\Controller\Compare;

use Enter\Http;
use EnterAggregator\LoggerTrait;
use EnterAggregator\SessionTrait;
use EnterQuery as Query;
use EnterTerminal\Controller;

class SetProduct {
    use LoggerTrait, SessionTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
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
        $compareRepository->setProductForObject($compare, $compareProduct);

        // сохранение сравнения в сессию
        $compareRepository->saveObjectToHttpSession($session, $compare);

        // response
        return (new Controller\Compare())->execute($request);
    }
}