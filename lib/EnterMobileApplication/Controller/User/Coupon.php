<?php

namespace EnterMobileApplication\Controller\User {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\DebugContainerTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\User\Coupon\Response;
    use EnterMobileApplication\Controller\ProductListingTrait;

    class Coupon {
        use ConfigTrait, LoggerTrait, CurlTrait, DebugContainerTrait, ProductListingTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();

            // ответ
            $response = new Response();

            $token = is_scalar($request->query['token']) ? (string)$request->query['token'] : null;
            if (!$token) {
                throw new \Exception('Не указан token', Http\Response::STATUS_BAD_REQUEST);
            }

            $couponId = is_scalar($request->query['couponId']) ? (string)$request->query['couponId'] : null;
            if (!$couponId) {
                throw new \Exception('Не указан couponId', Http\Response::STATUS_BAD_REQUEST);
            }

            // ид региона
            $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request); // FIXME
            if (!$regionId) {
                throw new \Exception('Не указан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
            }

            // запрос пользователя
            $userItemQuery = new Query\User\GetItemByToken($token);
            $curl->prepare($userItemQuery);

            // запрос региона
            $regionQuery = new Query\Region\GetItemById($regionId);
            $curl->prepare($regionQuery);

            $curl->execute();

            // получение пользователя
            $user = (new \EnterRepository\User())->getObjectByQuery($userItemQuery);
            if ($user) {
                $response->token = $token;
            }

            // регион
            $region = (new \EnterRepository\Region())->getObjectByQuery($regionQuery);

            // список купонов
            $couponListQuery = new Query\Coupon\GetListByUserToken($token);
            $couponListQuery->setTimeout(10 * $config->coreService->timeout);
            $curl->prepare($couponListQuery);

            // список лимитов серий купонов
            $seriesLimitListQuery = new Query\Coupon\Series\GetLimitList();
            $seriesLimitListQuery->setTimeout(3 * $config->coreService->timeout);
            $curl->prepare($seriesLimitListQuery);

            // список серий купонов
            $seriesListQuery = new Query\Coupon\Series\GetList(/*$user->isEnterprizeMember ? '1' : null*/null);
            $seriesListQuery->setTimeout(3 * $config->coreService->timeout);
            $curl->prepare($seriesListQuery);

            $curl->execute();

            $response->coupon = call_user_func(function() use (&$couponListQuery, $couponId) {
                $coupons = array_filter(
                    (new \EnterRepository\Coupon())->getObjectListByQuery($couponListQuery),
                    function(Model\Coupon $coupon) use($couponId) {
                        return $couponId === $coupon->id;
                    }
                );

                return reset($coupons) ?: null;
            });

            if (!$response->coupon) {
                return new Http\JsonResponse($response);
            }

            // TODO: вынести в репозиторий
            $response->couponSeries = call_user_func(function() use (&$response, &$seriesListQuery, &$seriesLimitListQuery) {
                $couponSeries = array_filter(
                    (new \EnterRepository\Coupon\Series())->getObjectListByQuery($seriesListQuery, $seriesLimitListQuery),
                    function(Model\Coupon\Series $series) use (&$response) {
                        return ($response->coupon->seriesId === $series->id);
                    }
                );

                return reset($couponSeries) ?: null;
            });

            // срезы для серий купонов
            $sliceTokensBySeriesId = [];
            if ($response->couponSeries->productSegment->url && preg_match('/\/slices\/([\w\d-_]+)/', $response->couponSeries->productSegment->url, $matches)) {
                if (!empty($matches[1])) {
                    $sliceTokensBySeriesId[$response->couponSeries->id] = $matches[1];
                }
            }
            try {
                if ($sliceTokensBySeriesId) {
                    $sliceListQuery = new Query\Product\Slice\GetListByTokenList(array_values($sliceTokensBySeriesId));
                    $curl->prepare($sliceListQuery);

                    $curl->execute();

                    /** @var Model\Product\Slice[] $slicesByToken */
                    $slicesByToken = [];
                    foreach ($sliceListQuery->getResult() as $item) {
                        $slice = new Model\Product\Slice($item);
                        $slicesByToken[$slice->token] = $slice;
                    }

                    $sliceToken = @$sliceTokensBySeriesId[$response->couponSeries->id] ?: null;
                    $response->couponSeries->slice = @$slicesByToken[$sliceToken] ?: null;
                }
            } catch (\Exception $e) {
                $this->getLogger()->push(['type' => 'error', 'error' => $e, 'tag' => ['critical']]);
            }

            // товары из среза
            if ($response->couponSeries->slice) {
                try {
                    $filterRepository = new \EnterMobile\Repository\Product\Filter(); // FIXME!!!
                    // фильтры в настройках среза
                    $baseRequestFilters = $filterRepository->getRequestObjectListByHttpRequest(new Http\Request($response->couponSeries->slice->filters));

                    // контроллер
                    $controller = new \EnterAggregator\Controller\ProductList();
                    // запрос для контроллера
                    $controllerRequest = $controller->createRequest();
                    $controllerRequest->config->mainMenu = false;
                    $controllerRequest->config->parentCategory = false;
                    $controllerRequest->config->branchCategory = false;
                    $controllerRequest->regionId = $regionId;
                    $controllerRequest->categoryCriteria = []; // критерий получения категории товара
                    $controllerRequest->pageNum = 1;
                    $controllerRequest->limit = 24;
                    $controllerRequest->filterRepository = $filterRepository;
                    $controllerRequest->baseRequestFilters = $baseRequestFilters;
                    $controllerRequest->requestFilters = $baseRequestFilters;
                    $controllerRequest->userToken = $token;
                    // ответ от контроллера
                    $controllerResponse = $controller->execute($controllerRequest);

                    $response->couponSeries->products = $this->getProductList($controllerResponse->products);
                } catch (\Exception $e) {
                    $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller']]);
                }
            }

            if (2 == $config->debugLevel) $this->getLogger()->push(['response' => $response]);

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterMobileApplication\Controller\User\Coupon {
    use EnterModel as Model;

    class Response {
        /** @var string */
        public $token;
        /** @var Model\Coupon */
        public $coupon;
        /** @var Model\Coupon\Series */
        public $couponSeries;
    }
}