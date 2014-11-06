<?php

namespace EnterAggregator\Controller\Product {

    use Enter\Http;
    use EnterAggregator\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterRepository as Repository;
    use EnterQuery as Query;
    use EnterMobile\Model;
    use EnterAggregator\Model\Context\Product\RecommendedList as Context;

    class RecommendedList {
        use ConfigTrait, LoggerTrait, CurlTrait;

        public function execute(
            $regionId,
            $productId,
            Context $context
        ) {
            $logger = $this->getLogger();
            $config = $this->getConfig();
            $curl = $this->getCurl();
            $productRepository = new \EnterRepository\Product();

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
            $region = (new \EnterRepository\Region())->getObjectByQuery($regionQuery);

            // запрос товара
            $productItemQuery = new Query\Product\GetItemById($productId, $region->id);
            $curl->prepare($productItemQuery);

            $curl->execute();

            // товар
            $product = $productRepository->getObjectByQuery($productItemQuery);

            // запрос идетификаторов товаров "с этим товаром также покупают"
            $crossSellItemToItemsListQuery = null;
            if ($context->alsoBought) {
                $crossSellItemToItemsListQuery = new Query\Product\Relation\CrossSellItemToItems\GetIdListByProductId($product->id);
                $curl->prepare($crossSellItemToItemsListQuery);
            }

            // запрос идетификаторов товаров "похожие товары"
            $upSellItemToItemsListQuery = null;
            if ($context->similarIdList) {
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
            $alsoBoughtIdList = $product->relatedIds;
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
            $productIds = array_unique(array_merge($alsoBoughtIdList, $similarIdList, $alsoViewedIdList));

            // запрос списка товаров
            $productListQueries = [];
            foreach (array_chunk($productIds, $config->curl->queryChunkSize) as $idsInChunk) {
                $productListQuery = new Query\Product\GetListByIdList($idsInChunk, $region->id);
                $curl->prepare($productListQuery);

                $productListQueries[] = $productListQuery;
            }

            $curl->execute();

            // товары
            $productsById = $productRepository->getIndexedObjectListByQueryList($productListQueries);

            foreach ($alsoBoughtIdList as $i => $productId) {
                // SITE-2818 из списка товаров "с этим товаром также покупают" убираем товары, которые есть только в магазинах
                /** @var \EnterModel\Product|null $product */
                $product = isset($productsById[$productId]) ? $productsById[$productId] : null;
                if (!$product) continue;

                if ($product->isInShopOnly || !$product->isBuyable) {
                    unset($alsoBoughtIdList[$i]);
                }
            }

            $chunkedIds = [$alsoBoughtIdList, $similarIdList, $alsoViewedIdList];
            $ids = [];
            foreach ($chunkedIds as &$ids) {
                // удаляем ид товаров, которых нет в массиве $productsById
                $ids = array_intersect($ids, array_keys($productsById));
                // применяем лимит
                $ids = array_slice($ids, 0, $config->product->itemsInSlider);
            }
            unset($ids, $chunkedIds);


            $response->product = $product;
            $response->productsById = $productsById;
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
        /** @var Model\Product|null */
        public $product;
        /** @var Model\Product[] */
        public $productsById;
        /** @var string[] */
        public $alsoBoughtIdList = [];
        /** @var string[] */
        public $similarIdList = [];
        /** @var string[] */
        public $alsoViewedIdList = [];
    }
}