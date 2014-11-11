<?php

namespace EnterAggregator\Controller\Product {

    use Enter\Http;
    use EnterAggregator\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterRepository as Repository;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterAggregator\Model\Context\Product\RecommendedList as Context;

    class RecommendedListByProduct {
        use ConfigTrait, LoggerTrait, CurlTrait;

        /**
         * @param string $regionId
         * @param string[] $productIds
         * @param Context $context
         * @return RecommendedList\Response
         */
        public function execute(
            $regionId,
            array $productIds,
            Context $context
        ) {
            $logger = $this->getLogger();
            $config = $this->getConfig();
            $curl = $this->getCurl();
            $productRepository = new Repository\Product();

            // response
            $response = new RecommendedList\Response();

            // запрос региона
            $regionQuery = new Query\Region\GetItemById($regionId);
            $curl->prepare($regionQuery);

            $curl->execute();

            // регион
            $response->region = (new Repository\Region())->getObjectByQuery($regionQuery);

            // запрос региона
            $regionQuery = new Query\Region\GetItemById($regionId);
            $curl->prepare($regionQuery);

            $curl->execute();

            // регион
            $region = (new Repository\Region())->getObjectByQuery($regionQuery);

            // запрос товара
            $productListQuery = new Query\Product\GetListByIdList($productIds, $region->id);
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
            if ($context->alsoBought) {
                $crossSellItemToItemsListQuery =
                    $product
                    ? new Query\Product\Relation\CrossSellItemToItems\GetIdListByProductId($product->id)
                    : new Query\Product\Relation\CrossSellItemToItems\GetIdListByProductIdList(array_keys($productsById))
                ;
                $curl->prepare($crossSellItemToItemsListQuery);
            }

            // запрос идетификаторов товаров "похожие товары"
            $upSellItemToItemsListQuery = null;
            if ($context->similar) {
                $upSellItemToItemsListQuery = new Query\Product\Relation\UpSellItemToItems\GetIdListByProductId($product->id);
                $curl->prepare($upSellItemToItemsListQuery);
            }

            // запрос идетификаторов товаров "с этим товаром также смотрят"
            $itemToItemsListQuery = null;
            if ($context->alsoViewed) {
                $itemToItemsListQuery = new Query\Product\Relation\ItemToItems\GetIdListByProductId($product->id);
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
            $productListQueries = [];
            foreach (array_chunk($recommendedIds, $config->curl->queryChunkSize) as $idsInChunk) {
                $productListQuery = new Query\Product\GetListByIdList($idsInChunk, $region->id);
                $curl->prepare($productListQuery);

                $productListQueries[] = $productListQuery;
            }

            $curl->execute();

            // товары
            $recommendedProductsById = $productRepository->getIndexedObjectListByQueryList($productListQueries);

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
                $ids = array_slice($ids, 0, $config->product->itemsInSlider);
            }
            unset($ids, $chunkedIds);

            // сортировка по наличию
            //$productRepository->sortByStockStatus($alsoBoughtIdList, $recommendedProductsById);
            //$productRepository->sortByStockStatus($similarIdList, $recommendedProductsById);
            //$productRepository->sortByStockStatus($alsoViewedIdList, $recommendedProductsById);


            $response->productsById = $productsById;
            $response->recommendedProductsById = $recommendedProductsById;
            $response->alsoBoughtIdList = $alsoBoughtIdList;
            $response->similarIdList = $similarIdList;
            $response->alsoViewedIdList = $alsoViewedIdList;

            return $response;
        }
    }
}

namespace EnterAggregator\Controller\Product\RecommendedList {
    use EnterModel as Model;

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