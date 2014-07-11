<?php

namespace EnterTerminal\Controller\Compare;

use Enter\Http;
use EnterSite\ConfigTrait;
use EnterSite\CurlClientTrait;
use EnterSite\LoggerTrait;
use EnterSite\SessionTrait;
use EnterCurlQuery as Query;
use EnterSite\Repository;
use EnterTerminal\Controller;

class Clear {
    use ConfigTrait, LoggerTrait, CurlClientTrait, SessionTrait {
        ConfigTrait::getConfig insteadof LoggerTrait, CurlClientTrait, SessionTrait;
        LoggerTrait::getLogger insteadof CurlClientTrait, SessionTrait;
    }

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