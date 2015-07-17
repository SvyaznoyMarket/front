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


            $page = new Page();
            (new Repository\Page\User\EnterprizeCoupon())->buildObjectByRequest($page, $pageRequest);

            // рендер
            $renderer = $this->getRenderer();
            $renderer->setPartials([
                'content' => 'page/private/prizecoupon'
            ]);

            $content = $renderer->render('layout/footerless', $page);

            return new Http\Response($content);



//            new
//            $couponQuery = new Query\Coupon\Series\GetItemByUi($coupon);
//            $couponQuery->setTimeout(10 * $config->coreService->timeout);
//            $curl->prepare($couponQuery);
//            $curl->execute();
//
//            echo '<pre>';
//            print_r ($couponQuery->getResult());
//            echo '</pre>';
//
//
//            $couponQuery = new Query\Coupon\Series\GetListByUi($coupon);
//            $couponQuery->setTimeout(10 * $config->coreService->timeout);
//            $curl->prepare($couponQuery);
//            $curl->execute();
//            echo 'list<br/>';
//            $coupons = (new \EnterRepository\Coupon())->getObjectListByQuery($couponQuery);
//
//            echo '<pre>';
//            print_r ($couponQuery->getResult());
//            echo '</pre>';

//            $result = (new \EnterRepository\Coupon\Series())->getObjectListByQuery($couponQuery);



//
//            echo '<pre>';
//            print_r ($result);
//            echo '</pre>';




//            $config = $this->getConfig();
//            $curl = $this->getCurl();
//            $user = new \EnterMobile\Repository\User();
//
//            $token = $user->getTokenByHttpRequest($request);
//            if (!$token) {
//                throw new \Exception('Не указан token', Http\Response::STATUS_BAD_REQUEST);
//            }
//
//            $userItemQuery = new Query\User\GetItemByToken($token);
//            $curl->prepare($userItemQuery);
//
//            $curl->execute();
//
//            $userObj = (new \EnterRepository\User())->getObjectByQuery($userItemQuery);
//
//            // список купонов
//            $couponListQuery = new Query\Coupon\GetListByUserToken($token);
//            $couponListQuery->setTimeout(10 * $config->coreService->timeout);
//            $curl->prepare($couponListQuery);
//
//            // список лимитов серий купонов
//            $seriesLimitListQuery = new Query\Coupon\Series\GetLimitList();
//            $seriesLimitListQuery->setTimeout(3 * $config->coreService->timeout);
//            $curl->prepare($seriesLimitListQuery);
//
//            // список серий купонов
//            $seriesListQuery = new Query\Coupon\Series\GetList(/*$user->isEnterprizeMember ? '1' : null*/null);
//            $seriesListQuery->setTimeout(3 * $config->coreService->timeout);
//            $curl->prepare($seriesListQuery);
//
//            $curl->execute();
//
//            $usedSeriesIds = [];
//            $coupons = (new \EnterRepository\Coupon())->getObjectListByQuery($couponListQuery);
//            foreach ($coupons as $coupon) {
//                $usedSeriesIds[] = $coupon->seriesId;
//            }
//
//            $result = array_values( // TODO: вынести в репозиторий
//                array_filter( // фильрация серий купонов
//                    (new \EnterRepository\Coupon\Series())->getObjectListByQuery($seriesListQuery, $seriesLimitListQuery),
//                    function(Model\Coupon\Series $series) use (&$usedSeriesIds) {
//                        return in_array($series->id, $usedSeriesIds); // только те серии купонов, которые есть у ранее полученых купонов
//                    }
//                )
//            );

//            return new Http\JsonResponse([
//                'coupons' => $result
//            ]);

            $page = new Page();
            $renderer = $this->getRenderer();
            $renderer->setPartials([
                'content' => 'page/private/prizecoupon'
            ]);

            $content = $renderer->render('layout/footerless', $page);

            return new Http\Response($content);

//            return new Http\JsonResponse();
        }
    }
}