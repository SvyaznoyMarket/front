<?php

namespace EnterTerminal\Controller\Compare;

use Enter\Http;
use EnterTerminal\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\SessionTrait;
use EnterCurlQuery as Query;
use EnterTerminal\Controller;

class Clear {
    use ConfigTrait, LoggerTrait, CurlTrait, SessionTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $session = $this->getSession();
        $compareRepository = new \EnterRepository\Compare();

        // корзина из сессии
        $compare = $compareRepository->getObjectByHttpSession($session);

        // удаление товаров
        $compare->product = [];

        // сохранение корзины в сессию
        $compareRepository->saveObjectToHttpSession($session, $compare);

        // response
        return (new Controller\Compare())->execute($request);
    }
}