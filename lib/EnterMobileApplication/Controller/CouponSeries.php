<?php

namespace EnterMobileApplication\Controller {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\DebugContainerTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\CouponSeries\Response;

    class CouponSeries {
        use ConfigTrait, LoggerTrait, CurlTrait, DebugContainerTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();
            $couponSeriesRepository = new \EnterRepository\Coupon\Series();

            // ответ
            $response = new Response();

            $token = is_scalar($request->query['token']) ? (string)$request->query['token'] : null;
            $seriesId = is_scalar($request->query['id']) ? (string)$request->query['id'] : null;
            if (!$seriesId) {
                throw new \Exception('Не указан параметр id', Http\Response::STATUS_BAD_REQUEST);
            }

            // ид региона
            $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request); // FIXME
            if (!$regionId) {
                throw new \Exception('Не указан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
            }

            // запрос пользователя
            $userItemQuery = null;
            if ($token) {
                $userItemQuery = (0 !== strpos($token, 'anonymous-')) ? new Query\User\GetItemByToken($token) : null;

                if ($userItemQuery) {
                    $curl->prepare($userItemQuery)->execute();
                }
            }

            // получение пользователя
            $user = $userItemQuery ? (new \EnterRepository\User())->getObjectByQuery($userItemQuery, false) : null;
            if ($user) {
                $response->token = $token;
            }

            // список купонов
            $couponListQuery = null;
            if ($user && $token) {
                $couponListQuery = new Query\Coupon\GetListByUserToken($token);
                $couponListQuery->setTimeout(5 * $config->coreService->timeout);
                $curl->prepare($couponListQuery);
            }

            // список лимитов серий купонов
            $seriesLimitListQuery = new Query\Coupon\Series\GetLimitList();
            $seriesLimitListQuery->setTimeout(5 * $config->coreService->timeout);
            $curl->prepare($seriesLimitListQuery);

            // список серий купонов
            $seriesListQuery = new Query\Coupon\Series\GetListByUi($seriesId);
            $seriesListQuery->setTimeout(5 * $config->coreService->timeout);
            $curl->prepare($seriesListQuery);

            $curl->execute();

            $usedSeriesIds = [];

            if ($couponListQuery) {
                $coupons = (new \EnterRepository\Coupon())->getObjectListByQuery($couponListQuery);
                foreach ($coupons as $coupon) {
                    $usedSeriesIds[] = $coupon->seriesId;
                }
            }

            $response->couponSeries = $couponSeriesRepository->getObjectListByQuery($seriesListQuery, $seriesLimitListQuery);
            $response->couponSeries = $couponSeriesRepository->filterObjectList($response->couponSeries, $usedSeriesIds, $user, $seriesId);
            $response->couponSeries = reset($response->couponSeries) ?: null;

            if ($response->couponSeries) {
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
                        $controllerRequest->limit = 20;
                        $controllerRequest->filterRepository = $filterRepository;
                        $controllerRequest->baseRequestFilters = $baseRequestFilters;
                        $controllerRequest->requestFilters = $baseRequestFilters;
                        $controllerRequest->userToken = $token;
                        // ответ от контроллера
                        $controllerResponse = $controller->execute($controllerRequest);

                        $response->couponSeries->products = $controllerResponse->products;
                    } catch (\Exception $e) {
                        $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller']]);
                    }
                }
            }

            if (2 == $config->debugLevel) $this->getLogger()->push(['response' => $response]);

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterMobileApplication\Controller\CouponSeries {
    use EnterModel as Model;

    class Response {
        /** @var string|null */
        public $token;
        /** @var Model\Coupon\Series|null */
        public $couponSeries;
    }
}