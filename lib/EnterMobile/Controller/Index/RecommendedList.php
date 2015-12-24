<?php

namespace EnterMobile\Controller\Index;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterAggregator\DebugContainerTrait;
use EnterMobile\Repository;
use EnterQuery as Query;
use EnterMobile\Model;
use EnterMobile\Model\Page\Index\RecommendedList as Page;

class RecommendedList {
    use ConfigTrait, MustacheRendererTrait, DebugContainerTrait;

    public function execute(Http\Request $request) {
        $config = $this->getConfig();

        // ид региона
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        // контроллер
        $controller = new \EnterAggregator\Controller\Index\RecommendedList();
        // запрос для контроллера
        $controllerRequest = $controller->createRequest();
        $controllerRequest->regionId = $regionId;
        $controllerRequest->httpRequest = $request;

        $controllerResponse = $controller->execute($controllerRequest);

        $pageRequest = new Repository\Page\Index\RecommendedList\Request();

        $pageRequest->popularItems = $controllerResponse->popularItems;
        $pageRequest->personalItems = $controllerResponse->personalItems;
        $pageRequest->viewedItems = $controllerResponse->viewedItems;

        // страница
        $page = new Page();
        (new Repository\Page\Index\RecommendedList())->buildObjectByRequest($page, $pageRequest);
        if ($config->debugLevel) $this->getDebugContainer()->page = $page;

        return new Http\JsonResponse([
            'result' => $page
        ]);

    }
}