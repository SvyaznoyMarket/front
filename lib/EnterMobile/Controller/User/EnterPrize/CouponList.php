<?php

namespace EnterMobile\Controller\User\EnterPrize {

    use Enter\Http;
    use EnterMobile\ConfigTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\DebugContainerTrait;
    use EnterAggregator\MustacheRendererTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobile\Repository;
    use EnterMobile\Model\Page\User\EnterprizeList as Page;


    class CouponList {
        use ConfigTrait,
            LoggerTrait,
            CurlTrait,
            DebugContainerTrait,
            MustacheRendererTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();

            // ид региона
            $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

            // контроллер
            $controller = new \EnterAggregator\Controller\User\EnterprizeList();
            // запрос для контроллера
            $controllerRequest = $controller->createRequest();
            $controllerRequest->regionId = $regionId;
            $controllerRequest->httpRequest = $request;
            // ответ
            $controllerResponse = $controller->execute($controllerRequest);

            if ($controllerResponse->redirect) {
                return $controllerResponse->redirect;
            }



            //запрос для получения страницы
            $pageRequest = new Repository\Page\User\Enterprize\Request();
            $pageRequest->httpRequest = $request;
            $pageRequest->region = $controllerResponse->region;
            $pageRequest->user = $controllerResponse->user;
            $pageRequest->cart = $controllerResponse->cart;
            $pageRequest->mainMenu = $controllerResponse->mainMenu;
            $pageRequest->coupons = $controllerResponse->coupons;
            $pageRequest->userMenu = $controllerResponse->userMenu;


            $page = new Page();
            (new Repository\Page\User\EnterprizeList())->buildObjectByRequest($page, $pageRequest);


            // рендер
            $renderer = $this->getRenderer();
            $renderer->setPartials([
                'content' => 'page/private/prizelist'
            ]);

            $content = $renderer->render('layout/footerless', $page);

            return new Http\Response($content);
        }
    }
}