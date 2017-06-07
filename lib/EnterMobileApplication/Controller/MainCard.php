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

            /** @var \EnterQuery\User\GetItemByToken|null $userItemQuery */
            $userItemQuery = null;
            if (0 !== strpos($userAuthToken, 'anonymous-')) {
                $userItemQuery = new Query\User\GetItemByToken($userAuthToken);
                $curl->prepare($userItemQuery);
            }

            $popularItemsQuery = new Query\Product\Relation\ItemsToMain\GetIdList();
            $curl->prepare($popularItemsQuery);

            $curl->execute();

            $region = (new Repository\Region())->getObjectByQuery($regionQuery);

            if ($userItemQuery) {
                $user = (new \EnterRepository\User())->getObjectByQuery($userItemQuery, false);
            } else {
                $user = null;
            }

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

            $secretSalePromoListQuery = null;
            if ($user) {
                $secretSalePromoListQuery = new \EnterQuery\Promo\SecretSale\GetList();
                $curl->prepare($secretSalePromoListQuery);
            }
            
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

            // FRONT-110
            $productRepository->filterByStockStatus($popularProductIds, $products);
            $productRepository->filterByStockStatus($viewedProductIds, $products);

            $popularProducts = array_fill_keys($popularProductIds, null);
            $viewedProducts = array_fill_keys($viewedProductIds, null);
            $personalProducts = [];

            foreach ($products as $key => $product) {
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

            $popularProducts = $this->getProductList(array_filter($popularProducts), true);
            $personalProducts = $this->getProductList(array_filter($personalProducts), true);
            $viewedProducts = $this->getProductList(array_filter($viewedProducts), true);

            $recommendations = [];

            if ($popularProducts) {
                $recommendations[] = [
                    'name' => 'Популярные товары',
                    'products' => $popularProducts,
                ];
            }

            if ($personalProducts) {
                $recommendations[] = [
                    'name' => 'Мы рекомендуем',
                    'products' => $personalProducts,
                ];
            }

            return new Http\JsonResponse([
                'region' => $region,
                'recommendations' => $recommendations,
                'viewedProducts' => $viewedProducts,
                'mainMenu' => (new \EnterMobileApplication\Repository\MainMenu())->getObjectByQuery($mainMenuQuery, $categoryTreeQuery, $region, $this->getConfig(), $secretSalePromoListQuery && (bool)$secretSalePromoListQuery->getResult()),
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