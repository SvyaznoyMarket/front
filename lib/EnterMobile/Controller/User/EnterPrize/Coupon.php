<?php

namespace EnterMobile\Controller\User\EnterPrize {

    use Enter\Http;
    use EnterMobile\ConfigTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\DebugContainerTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobile\Repository;
    use EnterAggregator\MustacheRendererTrait;
    use EnterMobile\Model\Page\User\EnterprizeCoupon as Page;

    class Coupon {
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
            $controller = new \EnterAggregator\Controller\User\EnterprizeCoupon();
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
            $pageRequest = new Repository\Page\User\EnterprizeCoupon\Request();
            $pageRequest->httpRequest = $request;
            $pageRequest->region = $controllerResponse->region;
            $pageRequest->user = $controllerResponse->user;
            $pageRequest->cart = $controllerResponse->cart;
            $pageRequest->mainMenu = $controllerResponse->mainMenu;
            $pageRequest->coupon = $controllerResponse->coupon;
            $pageRequest->userMenu = $controllerResponse->userMenu;


            $page = new Page();
            (new Repository\Page\User\EnterprizeCoupon())->buildObjectByRequest($page, $pageRequest);
            if ($config->debugLevel) $this->getDebugContainer()->page = $page;

            // рендер
            $renderer = $this->getRenderer();
            $renderer->setPartials([
                'content' => 'page/private/prizecoupon'
            ]);

            $content = $renderer->render('layout/footerless', $page);

            return new Http\Response($content);
        }
    }
}