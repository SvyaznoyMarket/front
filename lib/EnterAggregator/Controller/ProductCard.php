<?php

namespace EnterAggregator\Controller {
    use EnterAggregator\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\Model\Context\ProductCard as Context;
    use EnterQuery as Query;
    use EnterRepository as Repository;
    use EnterModel as Model;

    class ProductCard {
        use ConfigTrait, CurlTrait;

        /**
         * @param string $regionId
         * @param array $productCriteria Критерий получения товара: ['id' => 1] или ['token' => 'hp4530s']
         * @param Context $context
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

            // запрос отзывов товара
            $reviewListQuery = null;
            if ($config->productReview->enabled && $context->review) {
                if ($config->productReview->enabled) {
                    $reviewListQuery = new Query\Product\Review\GetListByProductId($response->product->id, 1, $config->productReview->itemsInCard);
                    $curl->prepare($reviewListQuery);
                }
            }

            // запрос аксессуаров товара
            $accessoryListQuery = null;
            if ((bool)$response->product->accessoryIds) {
                $accessoryListQuery = new Query\Product\GetListByIdList(array_slice($response->product->accessoryIds, 0, $config->product->itemsInSlider), $response->region->id);
                $curl->prepare($accessoryListQuery);
            }

            // запрос наборов
            $kitListQuery = null;
            $kits = $response->product->kit;
            if ((bool)$response->product->kit) {
                $kitIds = array_map(function(Model\Product\Kit $kit) {
                    return $kit->id;
                }, $response->product->kit);

                // наборы из линиии
                if ($response->product->line) {
                    $lineItemQuery = new Query\Product\Line\GetItemByToken($response->product->line->token, $response->region->id);
                    $curl->prepare($lineItemQuery)->execute();
                    if ($line = (new Repository\Product\Line())->getObjectByQuery($lineItemQuery)) {
                        $response->product->line = $line;
                    }

                    $kitIds = array_merge($kitIds, (array)$response->product->line->productIds);
                }

                // дополнительные товары из других наборов для расчета доставки
                $kitIds = array_values(array_unique($kitIds));
                foreach (array_diff($kitIds, array_map(function(Model\Product\Kit $kit) { return $kit->id; }, $response->product->kit)) as $kitId) {
                    $kits[] = new Model\Product\Kit(['id' => $kitId]);
                }

                $kitListQuery = new Query\Product\GetListByIdList($kitIds, $response->region->id);
                $curl->prepare($kitListQuery);
            }

            // запрос доставки товара
            $deliveryListQuery = null;
            if (($context->delivery || (bool)$response->product->kit) && $response->product->isBuyable) {
                $cartProducts = [];
                $cartProducts[] = new Model\Cart\Product(['id' => $response->product->id, 'quantity' => 1]);
                foreach ($kits as $kit) {
                    $cartProducts[] = new Model\Cart\Product(['id' => $kit->id, 'quantity' => $kit->count ?: 1]);
                }

                $deliveryListQuery = new Query\Product\Delivery\GetListByCartProductList($cartProducts, $response->region->id);
                $curl->prepare($deliveryListQuery);
            }

            // запрос списка рейтингов товаров
            $ratingListQuery = null;
            if ($config->productReview->enabled) {
                $ratingListQuery = new Query\Product\Rating\GetListByProductIdList(array_merge([$response->product->id], (bool)$response->product->accessoryIds ? $response->product->accessoryIds : []));
                $curl->prepare($ratingListQuery);
            }

            // запрос трастфакторов товара
            $descriptionItemQuery = new Query\Product\GetDescriptionItemByUi($response->product->ui);
            $curl->prepare($descriptionItemQuery);

            // запрос настроек каталога
            $categoryItemQuery = null;
            if ($response->product->category && $response->product->category->ui) {
                $categoryItemQuery = new Query\Product\Category\GetItemByUi($response->product->category->ui, $regionId);
                $curl->prepare($categoryItemQuery);
            }

            // запрос доступности кредита
            $cart = new Model\Cart();
            (new Repository\Cart())->setProductForObject($cart, new Model\Cart\Product(['id' => $response->product->id, 'quantity' => 1]));
            $paymentGroupListQuery = new Query\PaymentGroup\GetList($response->region->id, $cart, ['isCredit' => true]);
            $curl->prepare($paymentGroupListQuery);

            $curl->execute();

            // меню
            if ($mainMenuQuery) {
                $response->mainMenu = (new Repository\MainMenu())->getObjectByQuery($mainMenuQuery, $categoryListQuery);
            }

            // отзывы товара
            $response->product->reviews = $reviewListQuery ? (new Repository\Product\Review())->getObjectListByQuery($reviewListQuery) : [];

            // видео товара
            //$productRepository->setVideoForObjectByQuery($response->product, $descriptionItemQuery);
            // 3d фото товара (maybe3d)
            //$productRepository->setPhoto3dForObjectByQuery($response->product, $descriptionItemQuery);
            // медиа товара
            $productRepository->setMediaForObjectByQuery($response->product, $descriptionItemQuery);

            // наборы
            $kitProductsById = $kitListQuery ? $productRepository->getIndexedObjectListByQueryList([$kitListQuery], function(&$item) {
                // оптимизация
                $item['media'] = [reset($item['media'])];
            }) : [];
            foreach ($kitProductsById as $kitProduct) {
                $kitProduct->kitCount = 0;
            }

            foreach ($response->product->kit as $kit) {
                /** @var Model\Product|null $kiProduct */
                $kiProduct = isset($kitProductsById[$kit->id]) ? $kitProductsById[$kit->id] : null;
                if (!$kiProduct) continue;

                $kiProduct->kitCount = $kit->count; // FIXME
            }
            $response->product->relation->kits = array_values($kitProductsById);

            // аксессуары
            if ($accessoryListQuery) {
                $productRepository->setAccessoryRelationForObjectListByQuery([$response->product->id => $response->product], $accessoryListQuery);
            }

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

            // категории аксессуаров
            $response->accessoryCategories = (new Repository\Product\Category())->getIndexedObjectListByProductListAndTokenList($response->product->relation->accessories, $response->catalogConfig ? $response->catalogConfig->accessoryCategoryTokens : []);

            // список магазинов, в которых есть товар
            $shopIds = [];
            foreach ($productsById as $product) {
                foreach ($product->stock as $stock) {
                    if (!$stock->shopId) continue;

                    $shopIds[] = $stock->shopId;
                }
            }
            if ((bool)$shopIds) {
                $shopListQuery = new Query\Shop\GetListByIdList($shopIds);
                $curl->prepare($shopListQuery);

                $curl->execute();

                foreach ($productsById as $product) {
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

            // настройки каталога
            $response->catalogConfig = $categoryItemQuery ? (new Repository\Product\Category())->getConfigObjectByQuery($categoryItemQuery) : null;

            // список рейтингов товаров
            if ($ratingListQuery) {
                $productRepository->setRatingForObjectListByQuery($productsById, $ratingListQuery);
            }

            // трастфакторы товара
            $productRepository->setDescriptionForObjectByQuery($response->product, $descriptionItemQuery);

            // доступность кредита
            $response->hasCredit =
                ($config->credit->directCredit->enabled && $response->product->isBuyable && ($response->product->price >= $config->credit->directCredit->minPrice)) // TODO: удалить часть условия после готовности CORE-2035
                ? (new Repository\PaymentGroup())->checkCreditObjectByListQuery($paymentGroupListQuery)
                : false;

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
        /** @var Model\Product\Category\Config */
        public $catalogConfig;
        /** @var Model\MainMenu|null */
        public $mainMenu;
        /** @var bool */
        public $hasCredit;
    }
}