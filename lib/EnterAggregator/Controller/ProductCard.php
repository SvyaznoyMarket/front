<?php

namespace EnterAggregator\Controller {
    use EnterAggregator\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\Model\Context;
    use EnterCurlQuery as Query;
    use EnterRepository as Repository;
    use EnterModel as Model;

    class ProductCard {
        use ConfigTrait, CurlTrait;

        /**
         * @param string $regionId
         * @param array $productCriteria Критерий получения товара: ['id' => 1] или ['token' => 'hp4530s']
         * @param \EnterAggregator\Model\Context $context
         * @throws \Exception
         * @return ProductCard\Response
         */
        public function execute(
            $regionId,
            array $productCriteria,
            Context $context
        ) {
            $config = $this->getConfig();
            $curl = $this->getCurl();
            $productRepository = new Repository\Product();

            // response
            $response = new ProductCard\Response();

            // запрос региона
            $regionQuery = new Query\Region\GetItemById($regionId);
            $curl->prepare($regionQuery);

            $curl->execute();

            // регион
            $response->region = (new Repository\Region())->getObjectByQuery($regionQuery);

            // запрос товара
            $productItemQuery = null;
            if (!empty($productCriteria['id'])) {
                $productItemQuery = new Query\Product\GetItemById($productCriteria['id'], $response->region->id);
            } else if (!empty($productCriteria['token'])) {
                $productItemQuery = new Query\Product\GetItemByToken($productCriteria['token'], $response->region->id);
            } else if (!empty($productCriteria['ui'])) {
                //$productItemQuery = new Query\Product\GetItemByUi($productCriteria['ui'], $response->region->id);
            }
            if (!$productItemQuery) {
                throw new \Exception('Неверный критерий для получения товара');
            }
            $curl->prepare($productItemQuery);

            $curl->execute();

            // товар
            $response->product = $productRepository->getObjectByQuery($productItemQuery);
            if (!$response->product) {
                return $response;
            }

            // запрос дерева категорий для меню
            $categoryListQuery = null;
            if ($context->mainMenu) {
                $categoryListQuery = new Query\Product\Category\GetTreeList($response->region->id, 3);
                $curl->prepare($categoryListQuery);
            }

            // запрос меню
            $mainMenuQuery = null;
            if ($context->mainMenu) {
                $mainMenuQuery = new Query\MainMenu\GetItem();
                $curl->prepare($mainMenuQuery);
            }

            // запрос доставки товара
            $deliveryListQuery = null;
            if ($response->product->isBuyable) {
                $cartProducts = [];
                $cartProducts[] = new Model\Cart\Product(['id' => $response->product->id, 'quantity' => 1]);
                foreach ($response->product->kit as $kit) {
                    $cartProducts[] = new Model\Cart\Product(['id' => $kit->id, 'quantity' => $kit->count]);
                }

                $deliveryListQuery = new Query\Product\Delivery\GetListByCartProductList($cartProducts, $response->region->id);
                $curl->prepare($deliveryListQuery);
            }

            // запрос отзывов товара
            $reviewListQuery = null;
            if ($config->productReview->enabled) {
                $reviewListQuery = new Query\Product\Review\GetListByProductId($response->product->id, 0, $config->productReview->itemsInCard);
                $curl->prepare($reviewListQuery);
            }

            // запрос видео товара
            $videoListQuery = new Query\Product\Media\Video\GetListByProductId($response->product->id);
            $curl->prepare($videoListQuery);

            // запрос аксессуаров товара
            $accessoryListQuery = null;
            if ((bool)$response->product->accessoryIds) {
                $accessoryListQuery = new Query\Product\GetListByIdList(array_slice($response->product->accessoryIds, 0, $config->product->itemsInSlider), $response->region->id);
                $curl->prepare($accessoryListQuery);
            }

            // запрос наборов
            $kitListQuery = null;
            if ((bool)$response->product->kit) {
                $kitListQuery = new Query\Product\GetListByIdList(array_map(function(Model\Product\Kit $kit) {
                    return $kit->id;
                }, $response->product->kit), $response->region->id);
                $curl->prepare($kitListQuery);
            }

            // запрос списка рейтингов товаров
            $ratingListQuery = null;
            if ($config->productReview->enabled) {
                $ratingListQuery = new Query\Product\Rating\GetListByProductIdList(array_merge([$response->product->id], (bool)$response->product->accessoryIds ? $response->product->accessoryIds : []));
                $curl->prepare($ratingListQuery);
            }

            // TODO: загрузка предков категории как в каталоге

            // запрос настроек каталога
            $catalogConfigQuery = null;
            if ($response->product->category) {
                $catalogConfigQuery = new Query\Product\Catalog\Config\GetItemByProductCategoryObject(array_merge($response->product->category->ascendants, [$response->product->category]), $response->product);
                $curl->prepare($catalogConfigQuery);
            }

            $curl->execute();

            // меню
            if ($mainMenuQuery) {
                $response->mainMenu = (new Repository\MainMenu())->getObjectByQuery($mainMenuQuery, $categoryListQuery);
            }

            // отзывы товара
            $response->product->reviews = $reviewListQuery ? (new Repository\Product\Review())->getObjectListByQuery($reviewListQuery) : [];

            // видео товара
            $productRepository->setVideoForObjectByQuery($response->product, $videoListQuery);
            // 3d фото товара (maybe3d)
            $productRepository->setPhoto3dForObjectByQuery($response->product, $videoListQuery);

            // наборы
            $kitProductsById = $kitListQuery ? $productRepository->getIndexedObjectListByQueryList([$kitListQuery], function(&$item) {
                // оптимизация
                $item['media'] = [reset($item['media'])];
            }) : [];
            foreach ($response->product->kit as $kit) {
                /** @var Model\Product|null $kiProduct */
                $kiProduct = isset($kitProductsById[$kit->id]) ? $kitProductsById[$kit->id] : null;
                if (!$kiProduct) continue;

                $kiProduct->kitCount = $kit->count; // FIXME
            }
            $response->product->relation->kits = array_values($kitProductsById);

            // группированные товары
            $productsById = [];
            foreach (array_merge([$response->product], $response->product->relation->accessories, $kitProductsById) as $iProduct) {
                /** @var Model\Product $iProduct */
                $productsById[$iProduct->id] = $iProduct;
            }

            // доставка товара
            if ($deliveryListQuery) {
                $productRepository->setDeliveryForObjectListByQuery($productsById, $deliveryListQuery);
            }

            // если у товара нет доставок, запрашиваем список магазинов, в которых товар может быть на витрине
            if (!(bool)$response->product->nearestDeliveries) {
                $shopsIds = [];
                foreach ($response->product->stock as $stock) {
                    if ($stock->shopId && ($stock->showroomQuantity > 0)) {
                        $shopsIds[] = $stock->shopId;
                    }
                }

                if ((bool)$shopsIds) {
                    $shopListQuery = new Query\Shop\GetListByIdList($shopsIds);
                    $curl->prepare($shopListQuery);

                    $curl->execute();

                    $productRepository->setNowDeliveryForObjectListByQuery([$response->product->id => $response->product], $shopListQuery);
                }
            }

            // настройки каталога
            $response->catalogConfig = $catalogConfigQuery ? (new Repository\Product\Catalog\Config())->getObjectByQuery($catalogConfigQuery) : null;

            // аксессуары
            if ($accessoryListQuery) {
                $productRepository->setAccessoryRelationForObjectListByQuery([$response->product->id => $response->product], $accessoryListQuery);
            }

            // список рейтингов товаров
            if ($ratingListQuery) {
                $productRepository->setRatingForObjectListByQuery($productsById, $ratingListQuery);
            }

            // категории аксессуаров
            $response->accessoryCategories = (new Repository\Product\Category())->getIndexedObjectListByProductListAndTokenList($response->product->relation->accessories, $response->catalogConfig ? $response->catalogConfig->accessoryCategoryTokens : []);

            return $response;
        }
    }
}

namespace EnterAggregator\Controller\ProductCard {
    use EnterModel as Model;

    class Response {
        /** @var Model\Region|null */
        public $region;
        /** @var Model\Product|null */
        public $product;
        /** @var Model\Product\Category[] */
        public $accessoryCategories = [];
        /** @var Model\Product\Catalog\Config */
        public $catalogConfig;
        /** @var Model\MainMenu|null */
        public $mainMenu;
    }
}