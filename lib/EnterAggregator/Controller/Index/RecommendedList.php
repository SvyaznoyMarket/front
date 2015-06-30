<?php

namespace EnterAggregator\Controller\Index {
    use Enter\Http;
    use EnterAggregator\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterRepository as Repository;

    class RecommendedList {
        use ConfigTrait,
            LoggerTrait,
            CurlTrait;

        public function execute(RecommendedList\Request $request) {
            $logger = $this->getLogger();
            $config = $this->getConfig();
            $curl = $this->getCurl();

            $response = new RecommendedList\Response();

            // регион
            $regionQuery = new Query\Region\GetItemById($request->regionId);
            $curl->prepare($regionQuery);
            $curl->execute();
            $response->region = (new Repository\Region())->getObjectByQuery($regionQuery);

            if ($request->config->popular) {
                $popularItemsQuery = new Query\Product\Relation\ItemsToMain\GetIdList();
                $curl->prepare($popularItemsQuery);
                $curl->execute();

                $popularItems = $popularItemsQuery->getResult();

                $response->popularItems = $this->buildProductObjects($popularItems, $response->region->id);
            }

            // т.к. retailrocket не ставит куку rrpusid для получения персональных рекомендаций - выдаем часть
            // популярных товаров как персональные
            if ($request->config->personal && isset($response->popularItems)) {
                foreach ($response->popularItems as $key => $item) {
                    if ($key % 2) {
                        $response->personalItems[] = $item;
                    }
                }
            }

            $response->viewedItems = $this->buildProductObjects(
                explode(',', (string)$request->httpRequest->cookies['product_viewed']),
                $response->region->id);

            return $response;
        }

        private function buildProductObjects($ids, $regionId) {
            $config = $this->getConfig();
            $curl = $this->getCurl();
            $productRepository = new Repository\Product();

            $productListQuery = new Query\Product\GetListByIdList($ids, $regionId);
            $productListQuery->setTimeout(1.5 * $config->coreService->timeout);
            $curl->prepare($productListQuery);

            $descriptionListQuery = new Query\Product\GetDescriptionListByIdList(
                $ids,
                [
                    'media'       => true,
                    'media_types' => ['main'],
                    'category'    => true
                ]
            );
            $curl->prepare($descriptionListQuery);
            $curl->execute();

            $viewedItems = $productRepository->getIndexedObjectListByQuery($productListQuery);

            $viewedProductsByUI = [];
            call_user_func(function() use (&$viewedItems, &$viewedProductsByUI) {
                foreach ($viewedItems as $product) {
                    if (!$product->isBuyable || $product->isInShopOnly) continue;

                    $viewedProductsByUI[$product->ui] = $product;
                }
            });

            $productRepository->setDescriptionForListByListQuery($viewedProductsByUI, $descriptionListQuery);

            return $viewedProductsByUI;
        }

        public function createRequest() {
            return new RecommendedList\Request();
        }
    }
}

namespace EnterAggregator\Controller\Index\RecommendedList {
    use EnterModel as Model;

    class Request {
        /** @var string */
        public $regionId;
        /** @var string[] */
        public $productIds = [];
        /** @var Request\Config */
        public $config;
        public $httpRequest;

        public function __construct() {
            $this->config = new Request\Config();
        }
    }

    class Response {
        /** @var Model\Region|null */
        public $region;
        /** @var Model\Product[] */
        public $recommendedProductsById;
        /** @var string[] */
        public $popularItems = [];
        /** @var string[] */
        public $personalItems = [];
        /** @var string[] */
        public $viewedItems = [];
    }
}

namespace EnterAggregator\Controller\Index\RecommendedList\Request {
    class Config {
        /**
         * Популярные товары
         *
         * @var bool
         */
        public $popular = true;
        /**
         * Персональные рекомендации
         *
         * @var bool
         */
        public $personal = true;
    }
}