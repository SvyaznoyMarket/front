<?php

namespace EnterMobileApplication\Controller {

    use Enter\Http;
    use EnterAggregator\SessionTrait;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterMobileApplication\Controller;
    use EnterMobileApplication\Repository;
    use EnterQuery as Query;
    use EnterModel as Model;

    class MainCard {
        use ConfigTrait, CurlTrait, SessionTrait, ProductListingTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();
            $session = $this->getSession();
            $productRepository = new \EnterRepository\Product();

            $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request); // FIXME
            $userAuthToken = is_scalar($request->query['token']) ? (string)$request->query['token'] : null;

            if (!$regionId) {
                throw new \Exception('Не указан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
            }

            if (!$userAuthToken) {
                throw new \Exception('Не задан параметр token', Http\Response::STATUS_BAD_REQUEST);
            }

            // запрос региона
            $regionQuery = new Query\Region\GetItemById($regionId);
            $curl->prepare($regionQuery);

            $popularItemsQuery = new Query\Product\Relation\ItemsToMain\GetIdList();
            $curl->prepare($popularItemsQuery);

            $curl->execute();

            $region = (new Repository\Region())->getObjectByQuery($regionQuery);

            try {
                $popularProductIds = $popularItemsQuery->getResult();
            } catch (\Exception $e) {
                $popularProductIds = [];
            }

            $viewedProductIds = explode(' ', trim($session->get('viewedProductIds')));

            // запрос дерева категорий для меню
            $categoryTreeQuery = (new \EnterRepository\MainMenu())->getCategoryTreeQuery(0);
            $curl->prepare($categoryTreeQuery);

            // запрос меню
            $mainMenuQuery = new Query\MainMenu\GetItem();
            $curl->prepare($mainMenuQuery);
            
            $promoListQuery = new Query\Promo\GetList(['app-mobile']);
            $curl->prepare($promoListQuery);

            $productListQueries = [];
            $descriptionListQueries = [];
            foreach (array_chunk(array_values(array_filter(array_unique(array_merge($popularProductIds, $viewedProductIds)))), $config->curl->queryChunkSize) as $idsInChunk) {
                $productListQuery = new Query\Product\GetListByIdList($idsInChunk, $region->id, ['related' => false]);
                $curl->prepare($productListQuery);
                $productListQueries[] = $productListQuery;

                $descriptionListQuery = new Query\Product\GetDescriptionListByIdList($idsInChunk, ['media' => true, 'media_types' => ['main']]);
                $curl->prepare($descriptionListQuery);
                $descriptionListQueries[] = $descriptionListQuery;
            }

            $configQuery = new Query\Config\GetListByKeys(['mnogoru', 'recommendations', 'enter_prize']);
            $curl->prepare($configQuery);

            $curl->execute();

            try {
                $products = $productRepository->getIndexedObjectListByQueryList($productListQueries, $descriptionListQueries);
            } catch (\Exception $e) {
                $products = [];
            }

            $popularProducts = array_fill_keys($popularProductIds, null);
            $viewedProducts = array_fill_keys($viewedProductIds, null);
            $personalProducts = [];

            foreach ($products as $key => $product) {
                if (!$product->isBuyable || $product->isInShopOnly) {
                    continue;
                }

                if (array_key_exists($product->id, $popularProducts)) {
                    $popularProducts[$product->id] = $product;
                }

                if (array_key_exists($product->id, $viewedProducts)) {
                    $viewedProducts[$product->id] = $product;
                }
            }

            // TODO для реализации персональных рекомендаций от retailrocket'а необходим HTTP api от retailrocket'а,
            // которого на данный момент у них нет (подробности в MAPI-69), поэтому пока заполняем первональные
            // рекомендации просмотренными товарами и товарами из главных рекомендаций
            $personalProducts = $viewedProducts;
            foreach (array_values($popularProducts) as $key => $product) {
                if ($key % 2) {
                    $personalProducts[] = $product;
                }
            }

            try {
                $config = $configQuery->getResult()['result'];
            } catch (\Exception $e) {
                $config = [];
            }

            $popularProducts = array_filter($popularProducts);
            $personalProducts = array_filter($personalProducts);
            $viewedProducts = array_filter($viewedProducts);

            return new Http\JsonResponse([
                'region' => $region,
                'recommendations' => [
                    [
                        'name' => 'Популярные товары',
                        'products' => $this->getProductList($popularProducts, true),
                    ],
                    [
                        'name' => 'Мы рекомендуем',
                        'products' => $this->getProductList($personalProducts, true),
                    ],
                ],
                'viewedProducts' => $this->getProductList($viewedProducts, true),
                'mainMenu' => (new \EnterRepository\MainMenu())->getObjectByQuery($mainMenuQuery, $categoryTreeQuery),
                'promos' => (new \EnterMobileApplication\Repository\Promo())->getObjectListByQuery($promoListQuery),
                'popularBrands' => array_map(function(\EnterModel\Brand $brand) {
                    return [
                        'name' => $brand->name,
                        'sliceId' => $brand->sliceId,
                        'media' => $brand->media,
                    ];
                }, (new \EnterRepository\Brand())->getPopularObjects()),
                'config' => $config,
            ]);
        }
    }
}