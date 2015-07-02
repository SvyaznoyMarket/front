<?php

namespace EnterAggregator\Controller\Product {

    use Enter\Http;
    use EnterAggregator\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterRepository as Repository;

    class RecommendedListByProduct {
        use ConfigTrait, LoggerTrait, CurlTrait;

        /**
         * @param RecommendedListByProduct\Request $request
         * @return RecommendedListByProduct\Response
         * @throws \Exception
         */
        public function execute(RecommendedListByProduct\Request $request) {
            $logger = $this->getLogger();
            $config = $this->getConfig();
            $curl = $this->getCurl();
            $productRepository = new Repository\Product();

            // response
            $response = new RecommendedListByProduct\Response();

            // запрос региона
            $regionQuery = new Query\Region\GetItemById($request->regionId);
            $curl->prepare($regionQuery);

            $curl->execute();

            // регион
            $region = (new Repository\Region())->getObjectByQuery($regionQuery);

            // запрос товара
            $productListQuery = new Query\Product\GetListByIdList($request->productIds, $region->id);
            $curl->prepare($productListQuery);

            $curl->execute();

            // товары
            $productsById = $productRepository->getIndexedObjectListByQueryList([$productListQuery]);

            // товар
            /** @var Model\Product|null $product */
            $product = reset($productsById) ?: null;

            if (!$product) {
                return $response;
            }

            // запрос идетификаторов товаров "с этим товаром также покупают"
            $crossSellItemToItemsListQuery = null;
            if ($request->config->alsoBought) {
                $crossSellItemToItemsListQuery =
                    $product
                    ? new Query\Product\Relation\CrossSellItemToItems\GetIdListByProductId($product->id)
                    : new Query\Product\Relation\CrossSellItemToItems\GetIdListByProductIdList(array_keys($productsById))
                ;
                $crossSellItemToItemsListQuery->setTimeout(1.5 * $config->retailRocketService->timeout);
                $curl->prepare($crossSellItemToItemsListQuery);
            }

            // запрос идетификаторов товаров "похожие товары"
            $upSellItemToItemsListQuery = null;
            if ($request->config->similar) {
                $upSellItemToItemsListQuery = new Query\Product\Relation\UpSellItemToItems\GetIdListByProductId($product->id);
                $upSellItemToItemsListQuery->setTimeout(1.5 * $config->retailRocketService->timeout);
                $curl->prepare($upSellItemToItemsListQuery);
            }

            // запрос идетификаторов товаров "с этим товаром также смотрят"
            $itemToItemsListQuery = null;
            if ($request->config->alsoViewed) {
                $itemToItemsListQuery = new Query\Product\Relation\ItemToItems\GetIdListByProductId($product->id);
                $itemToItemsListQuery->setTimeout(1.5 * $config->retailRocketService->timeout);
                $curl->prepare($itemToItemsListQuery);
            }

            $curl->execute();

            // идетификаторы товаров "с этим товаром также покупают"
            $alsoBoughtIdList = [];
            foreach ($productsById as $iProduct) {
                $alsoBoughtIdList = array_merge($alsoBoughtIdList, (array)$iProduct->relatedIds);
            }
            try {
                $alsoBoughtIdList = array_unique(array_merge(
                    $alsoBoughtIdList,
                    $crossSellItemToItemsListQuery ? $crossSellItemToItemsListQuery->getResult() : []
                ));
            } catch (\Exception $e) {
                $logger->push(['type' => 'warn', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['product.recommendation']]);
            }

            // идетификаторы товаров "похожие товары"
            $similarIdList = [];
            try {
                $similarIdList = $upSellItemToItemsListQuery ? array_unique($upSellItemToItemsListQuery->getResult()) : [];
            } catch (\Exception $e) {
                $logger->push(['type' => 'warn', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['product.recommendation']]);
            }

            // идетификаторы товаров "с этим товаром также смотрят"
            $alsoViewedIdList = [];
            try {
                $alsoViewedIdList = $itemToItemsListQuery ? array_unique($itemToItemsListQuery->getResult()) : [];
            } catch (\Exception $e) {
                $logger->push(['type' => 'warn', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['product.recommendation']]);
            }

            // список всех идентификаторов товаров
            $recommendedIds = array_unique(array_merge($alsoBoughtIdList, $similarIdList, $alsoViewedIdList));

            // запрос списка товаров
            $descriptionListQueries = [];
            $productListQueries = [];
            foreach (array_chunk($recommendedIds, $config->curl->queryChunkSize) as $idsInChunk) {
                $productListQuery = new Query\Product\GetListByIdList($idsInChunk, $region->id);
                $productListQuery->setTimeout(1.5 * $config->coreService->timeout);
                $curl->prepare($productListQuery);
                $productListQueries[] = $productListQuery;

                // запрос списка медиа для товаров
                $descriptionListQuery = new Query\Product\GetDescriptionListByIdList(
                    $idsInChunk,
                    [
                        'media'       => true,
                        'media_types' => ['main'], // только главная картинка
                        'category'    => true,
                        'label'       => true,
                        'brand'       => true,
                    ]
                );
                $curl->prepare($descriptionListQuery);
                $descriptionListQueries[] = $descriptionListQuery;
            }

            $curl->execute();

            // товары
            $recommendedProductsById = $productRepository->getIndexedObjectListByQueryList($productListQueries);

            // товары по ui
            $productsByUi = [];
            call_user_func(function() use (&$recommendedProductsById, &$productsByUi) {
                foreach ($recommendedProductsById as $product) {
                    $productsByUi[$product->ui] = $product;
                }
            });

            $productRepository->setDescriptionForListByListQuery($productsByUi, $descriptionListQueries);

            foreach ($alsoBoughtIdList as $i => $alsoBoughtId) {
                // SITE-2818 из списка товаров "с этим товаром также покупают" убираем товары, которые есть только в магазинах
                /** @var \EnterModel\Product|null $productsById */
                $iProduct = isset($recommendedProductsById[$alsoBoughtId]) ? $recommendedProductsById[$alsoBoughtId] : null;
                if (!$iProduct) continue;

                if ($iProduct->isInShopOnly || !$iProduct->isBuyable) {
                    unset($alsoBoughtIdList[$i]);
                }
            }

            $chunkedIds = [$alsoBoughtIdList, $similarIdList, $alsoViewedIdList];
            $ids = [];
            foreach ($chunkedIds as &$ids) {
                // удаляем ид товаров, которых нет в массиве $productsById
                $ids = array_intersect($ids, array_keys($recommendedProductsById));
                // применяем лимит
                //$ids = array_slice($ids, 0, $config->product->itemsInSlider);
                $ids = array_slice($ids, 0, 20);
            }
            unset($ids, $chunkedIds);

            // список магазинов, в которых есть товар
            $shopIds = [];
            foreach ($recommendedProductsById as $product) {
                foreach ($product->stock as $stock) {
                    if (!$stock->shopId) continue;

                    $shopIds[] = $stock->shopId;
                }
            }
            if ((bool)$shopIds) {
                $shopListQuery = new Query\Shop\GetListByIdList($shopIds);
                $curl->prepare($shopListQuery);

                $curl->execute();

                foreach ($recommendedProductsById as $product) {
                    $shopStatesByShopId = [];
                    foreach ($product->stock as $stock) {
                        if ($stock->shopId && (($stock->showroomQuantity + $stock->quantity) > 0)) {
                            $shopState = new Model\Product\ShopState();
                            $shopState->quantity = $stock->quantity;
                            $shopState->showroomQuantity = $stock->showroomQuantity;
                            $shopState->isInShowroomOnly = !$shopState->quantity && ($shopState->showroomQuantity > 0);

                            $shopStatesByShopId[$stock->shopId] = $shopState;
                        }
                    }
                    if ((bool)$shopStatesByShopId) {
                        $productRepository->setShopStateForObjectListByQuery([$product->id => $product], $shopStatesByShopId, $shopListQuery);
                    }
                }
            }

            // сортировка по наличию
            /*
            $productRepository->sortByStockStatus($alsoBoughtIdList, $recommendedProductsById);
            $productRepository->sortByStockStatus($similarIdList, $recommendedProductsById);
            $productRepository->sortByStockStatus($alsoViewedIdList, $recommendedProductsById);
            */

            // ответ
            $response->productsById = $productsById;
            $response->recommendedProductsById = $recommendedProductsById;
            $response->alsoBoughtIdList = $alsoBoughtIdList;
            $response->similarIdList = $similarIdList;
            $response->alsoViewedIdList = $alsoViewedIdList;

            return $response;
        }

        /**
         * @return RecommendedListByProduct\Request
         */
        public function createRequest() {
            return new RecommendedListByProduct\Request();
        }
    }
}

namespace EnterAggregator\Controller\Product\RecommendedListByProduct {
    use EnterModel as Model;

    class Request {
        /** @var string */
        public $regionId;
        /** @var string[] */
        public $productIds = [];
        /** @var Request\Config */
        public $config;

        public function __construct() {
            $this->config = new Request\Config();
        }
    }

    class Response {
        /** @var Model\Region|null */
        public $region;
        /** @var Model\Product[] */
        public $productsById;
        /** @var Model\Product[] */
        public $recommendedProductsById;
        /** @var string[] */
        public $alsoBoughtIdList = [];
        /** @var string[] */
        public $similarIdList = [];
        /** @var string[] */
        public $alsoViewedIdList = [];
    }
}

namespace EnterAggregator\Controller\Product\RecommendedListByProduct\Request {
    class Config {
        /**
         * Похожие товары
         *
         * @var bool
         */
        public $similar = false;
        /**
         * Также покупают
         *
         * @var bool
         */
        public $alsoBought = false;
        /**
         * Также смотрят
         *
         * @var bool
         */
        public $alsoViewed = false;
    }
}