<?php

namespace EnterMobileApplication\Controller\User\Favorite {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\SessionTrait;
    use EnterAggregator\DebugContainerTrait;
    use EnterQuery as Query;
    use EnterMobileApplication\Controller;
    use EnterMobileApplication\Repository;
    use EnterMobileApplication\Controller\User\Favorite\Get\Response;

    class Get {
        use ConfigTrait, LoggerTrait, CurlTrait, SessionTrait, DebugContainerTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();
            //$session = $this->getSession();

            // ответ
            $response = new Response();

            $token = is_scalar($request->query['token']) ? (string)$request->query['token'] : null;
            if (!$token) {
                throw new \Exception('Не указан token', Http\Response::STATUS_BAD_REQUEST);
            }

            try {
                $userItemQuery = new Query\User\GetItemByToken($token);
                $curl->prepare($userItemQuery);

                $curl->execute();

                $user = (new \EnterRepository\User())->getObjectByQuery($userItemQuery);
                if ($user) {
                    $response->token = $token;
                }

                $favoriteListQuery = new Query\User\Favorite\GetListByUserUi($user->ui);
                $favoriteListQuery->setTimeout(2 * $config->crmService->timeout);
                $curl->prepare($favoriteListQuery);

                $curl->execute();

                $favoriteListResult = $favoriteListQuery->getResult() + ['products' => []];

                $productUis = array_filter(
                    array_map(
                        function($item) {
                            return $item['uid'];
                        },
                        $favoriteListResult['products']
                    )
                );
                if ((bool)$productUis) {
                    $productListQuery = new Query\Product\GetListByUiList($productUis, $config->region->defaultId);
                    $curl->prepare($productListQuery);

                    $curl->execute();

                    $response->products = array_values(
                        (new \EnterRepository\Product())->getIndexedObjectListByQueryList([$productListQuery])
                    );
                }
            } catch (\Exception $e) {
                if ($config->debugLevel) $this->getDebugContainer()->error = $e;
            }

            if (2 == $config->debugLevel) $this->getLogger()->push(['response' => $response]);

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterMobileApplication\Controller\User\Favorite\Get {
    use EnterModel as Model;

    class Response {
        /** @var string */
        public $token;
        /** @var Model\Product[] */
        public $products = [];
    }
}