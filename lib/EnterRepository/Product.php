<?php

namespace EnterRepository;

use Enter\Http;
use Enter\Curl\Query;
use Enter\Logging\Logger;
use EnterAggregator\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterModel as Model;

class Product {
    use ConfigTrait, LoggerTrait;

    /** @var Logger */
    protected $logger;

    public function __construct() {
        $this->logger = $this->getLogger();
    }

    /**
     * @param Http\Request $request
     * @return string
     */
    public function getIdByHttpRequest(Http\Request $request) {
        return is_scalar($request->query['productId']) ? trim((string)$request->query['productId']) : null;
    }

    /**
     * @param Http\Request $request
     * @return string
     */
    public function getTokenByHttpRequest(Http\Request $request) {
        $token = explode('/', $request->query['productPath']);
        $token = end($token);

        return $token;
    }

    /**
     * @param Http\Request $request
     * @return int
     */
    public function getLimitByHttpRequest(Http\Request $request) {
        $limit = (int)$request->query['limit'];
        if (($limit >= 400) || ($limit <= 0)) {
            $limit = $this->getConfig()->product->itemPerPage;
        }

        return $limit;
    }

    /**
     * @param Query[] $listQueryList
     * @param Query[] $descriptionListQueryList
     * @return Model\Product|null
     */
    public function getObjectByQueryList(array $listQueryList, array $descriptionListQueryList = []) {
        $products = $this->getIndexedObjectListByQueryList($listQueryList, $descriptionListQueryList);
        if ($products) {
            return reset($products);
        }
        
        return null;
    }

    /**
     * Если задан $descriptionQueryList, то будут возвращены только те товары, которые есть и в $queries и в
     * $descriptionQueryList (согласно требованию от бэкэнда, если товар не вернулся ядром или scms, то такой товар не
     * следует отображать на сайте). Также товары будут наполнены данными из $descriptionQueryList.
     * @param mixed[] $listQueryList Допустимые значения массива: объекты Query, null
     * @param mixed[] $descriptionListQueryList Допустимые значения массива: объекты Query, null
     * @return Model\Product[]
     */
    public function getIndexedObjectListByQueryList(array $listQueryList, array $descriptionListQueryList = []) {
        /** @var Query[] $listQueryList */
        $listQueryList = array_filter($listQueryList);
        /** @var Query[] $descriptionListQueryList */
        $descriptionListQueryList = array_filter($descriptionListQueryList);
        
        $descriptionItemsById = [];
        foreach ($descriptionListQueryList as $query) {
            try {
                foreach ($query->getResult() as $item) {
                    if (!empty($item['core_id'])) {
                        $descriptionItemsById[(string)$item['core_id']] = $item;
                    }
                }
            } catch (\Exception $e) {
                trigger_error($e, E_USER_ERROR);
            }
        }
        
        $products = [];
        foreach ($listQueryList as $query) {
            try {
                foreach ($query->getResult() as $item) {
                    if (empty($item['id'])) {
                        continue;
                    }
                    
                    if (!$descriptionListQueryList) {
                        $products[(string)$item['id']] = new Model\Product($item);
                    } else if (!empty($descriptionItemsById[$item['id']])) {
                        $product = new Model\Product($item);
                        $this->setDescription($product, $descriptionItemsById[$item['id']]);
                        $products[(string)$item['id']] = $product;
                    }
                }
            } catch (\Exception $e) {
                trigger_error($e, E_USER_ERROR);
            }
        }
        
        return $products;
    }

    /**
     * @param Model\Product[] $products
     * @param Query[] $ratingListQueryList
     */
    public function setRatingForObjectListByQueryList(array $products, array $ratingListQueryList) {
        /** @var Model\Product[] $productsById */
        $productsById = [];
        foreach ($products as $product) {
            $productsById[$product->id] = $product;
        }

        try {
            foreach ($ratingListQueryList as $ratingListQuery) {
                foreach ($ratingListQuery->getResult() as $ratingItem) {
                    if (!isset($ratingItem['product_id'])) {
                        continue;
                    }

                    $productId = (string)$ratingItem['product_id'];

                    if (!isset($productsById[$productId])) {
                        continue;
                    }

                    $productsById[$productId]->rating = new Model\Product\Rating($ratingItem);
                }
            }
        } catch (\Exception $e) {
            $this->logger->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['repository']]);
        }
    }

    /**
     * @param Model\Product[] $productsById
     * @param Query $ratingListQuery
     */
    public function setRatingForObjectListByQuery(array $productsById, Query $ratingListQuery) {
        try {
            foreach ($ratingListQuery->getResult() as $ratingItem) {
                $productId = (string)$ratingItem['product_id'];
                if (!isset($productsById[$productId])) continue;

                $productsById[$productId]->rating = new Model\Product\Rating($ratingItem);
            }
        } catch (\Exception $e) {
            $this->logger->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['repository']]);
        }
    }

    /**
     * @param Model\Product[] $productsById
     * @param Query $deliveryListQuery
     */
    public function setDeliveryForObjectListByQuery(array $productsById, Query $deliveryListQuery) {
        try {
            $result = $deliveryListQuery->getResult();

            $productData = &$result['product_list'];
            $shopData = &$result['shop_list'];

            $regionData = [];
            foreach ($result['geo_list'] as $regionItem) {
                $regionData[(string)$regionItem['id']] = $regionItem;
            }

            foreach ($productData as $item) {
                $productId = (string)$item['id'];
                if (!$productId || !isset($productsById[$productId])) continue;

                if (isset($item['delivery_mode_list']) && is_array($item['delivery_mode_list'])) {
                    foreach ($item['delivery_mode_list'] as $deliveryItem) {
                        if (!isset($deliveryItem['date_list']) || !is_array($deliveryItem['date_list'])) continue;
    
                        // FIXME
                        if (in_array($deliveryItem['token'], ['now'])) continue;
    
                        $delivery = new Model\Product\Delivery();
                        $delivery->productId = $productId;
                        $delivery->id = (string)$deliveryItem['id'];
                        $delivery->token = (string)$deliveryItem['token'];
                        $delivery->isPickup = (bool)$deliveryItem['is_pickup'];
                        $delivery->price = (int)$deliveryItem['price'];

                        if (isset($deliveryItem['date_list']) && is_array($deliveryItem['date_list'])) {
                            foreach ($deliveryItem['date_list'] as $dateItem) {
                                if (isset($dateItem['date'])) {
                                    $delivery->dates[] = new \DateTime($dateItem['date']);
                                }
                            }
                        }

                        if (isset($deliveryItem['date_interval']['from']) && isset($deliveryItem['date_interval']['to'])) {
                            $delivery->dateInterval = new \EnterModel\DateInterval();
                            if ($deliveryItem['date_interval']['from']) {
                                $delivery->dateInterval->from = new \DateTime($deliveryItem['date_interval']['from']);
                            }
                            if ($deliveryItem['date_interval']['to']) {
                                $delivery->dateInterval->to = new \DateTime($deliveryItem['date_interval']['to']);
                            }
                        }

                        /** @var string $date Ближайшая дата доставки */
                        $date = reset($deliveryItem['date_list']);
                        $date = !empty($date['date']) ? $date['date'] : null;
                        $delivery->nearestDeliveredAt = $date ? new \DateTime($date) : null;
    
                        $day = 0;
                        foreach ($deliveryItem['date_list'] as $dateItem) {
                            $day++;
                            if ($day > 7) break;

                            if (isset($dateItem['shop_list']) && in_array($deliveryItem['token'], ['self', 'now'])) {
                                foreach ($dateItem['shop_list'] as $shopIntervalItem) {
                                    $shopId = (string)$shopIntervalItem['id'];
                                    $shopItem = (!array_key_exists($shopId, $delivery->shopsById) && isset($shopData[$shopId]['id'])) ? $shopData[$shopId] : null;
                                    if (!$shopItem) continue;
    
                                    $regionId = (string)$shopItem['geo_id'];
                                    if (array_key_exists($regionId, $regionData)) {
                                        $shopItem['geo'] = $regionData[$regionId];
                                    }
    
                                    $shop = new Model\Shop($shopItem);
    
                                    $delivery->shopsById[$shopId] = $shop;
                                }
                            }
                        }
    
                        $productsById[$productId]->deliveries[] = $delivery;
                    }
                }
                
                if (isset($item['prepay_rules']) && is_array($item['prepay_rules'])) {
                    $productLabelIds = array_map(function(\EnterModel\Product\Label $label) {
                        return $label->id;
                    }, $productsById[$productId]->labels);
            
                    if (isset($item['prepay_rules']['priorities']) && is_array($item['prepay_rules']['priorities'])) {
                        foreach ($item['prepay_rules']['priorities'] as $ruleNames => $priority) {
                            $ruleNames = explode(':', $ruleNames);

                            if (isset($ruleNames[0]) && isset($ruleNames[1]) && isset($item['prepay_rules'][$ruleNames[0]]) && is_array($item['prepay_rules'][$ruleNames[0]])) {
                                foreach ($item['prepay_rules'][$ruleNames[0]] as $ruleSubname => $ruleItem) {
                                    if (
                                        ($ruleNames[1] === '*' || $ruleNames[1] == $ruleSubname)
                                        && !empty($ruleItem['prepay_sum'])
                                        && (
                                            ($ruleNames[0] === 'deliveries' && isset($item['delivery_mode_list'][$ruleSubname]))
                                            || ($ruleNames[0] === 'labels' && in_array($ruleSubname, $productLabelIds))
                                            || ($ruleNames[0] === 'others')
                                        )
                                    ) {
                                        $productsById[$productId]->prepayment = new \EnterModel\Prepayment($ruleItem['prepay_sum']);
                                        break(2);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['repository']]);
        }
    }

    /**
     * @param \EnterModel\Product\Delivery[] $deliveries
     * @param bool $isPickup
     * @return \EnterModel\Product\Delivery|null
     */
    public function getDeliveriesWithMinDate($deliveries, $isPickup){
        $deliveryWithMinDate = null;
        $minDate = null;
        foreach ($deliveries as $delivery) {
            if ($isPickup == $delivery->isPickup) {
                foreach ($delivery->dates as $date) {
                    if ($date < $minDate || $minDate === null) {
                        $deliveryWithMinDate = $delivery;
                        $minDate = $date;
                    }
                }
            }
        }

        return $deliveryWithMinDate;
    }

    /**
     * @param Model\Product[] $productsById
     * @param Model\Product\ShopState[] $shopStatesByShopId
     * @param Query $shopListQuery
     */
    public function setShopStateForObjectListByQuery(array $productsById, array $shopStatesByShopId, Query $shopListQuery) {
        try {
            foreach ($productsById as $product) {
                foreach ($shopListQuery->getResult() as $shopItem) {
                    $shopId = (string)$shopItem['id'];

                    $shopState = isset($shopStatesByShopId[$shopId]) ? $shopStatesByShopId[$shopId] : null;
                    if (!$shopState) continue;

                    // оптимизация
                    $shopItem['description'] = '';
                    $shopItem['way_walk'] = '';
                    $shopItem['way_auto'] = '';
                    $shopItem['images'] = [];
                    $shopItem['medias'] = [];

                    $shopState->shop = new Model\Shop($shopItem);

                    $product->shopStates[] = $shopState;
                }
            }
        } catch (\Exception $e) {
            $this->logger->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['repository']]);
            //trigger_error($e, E_USER_ERROR);
        }
    }

    /**
     * @param Model\Product[] $productsById
     * @param Query $accessoryListQuery
     * @param Query $accessoryDescriptionListQuery
     */
    public function setAccessoryRelationForObjectListByQuery(array $productsById, Query $accessoryListQuery, $accessoryDescriptionListQuery) {
        try {
            $accessoryList = $this->getIndexedObjectListByQueryList([$accessoryListQuery], [$accessoryDescriptionListQuery]);
            foreach ($productsById as $product) {
                $product->relation->accessories = array_values(array_merge($product->relation->accessories, $accessoryList));
            }
        } catch (\Exception $e) {
            $this->logger->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['repository']]);
        }
    }

    /**
     * @param Model\Product $product
     * @param mixed $descriptionItem
     */
    private function setDescription(Model\Product $product, $descriptionItem) {
        try {
            if (isset($descriptionItem['slug'])) $product->token = (string)$descriptionItem['slug'];
            if (isset($descriptionItem['url'])) $product->link = (string)$descriptionItem['url'];
            if (isset($descriptionItem['name'])) $product->name = $descriptionItem['name'];
            if (isset($descriptionItem['name_web'])) $product->webName = $descriptionItem['name_web'];
            if (isset($descriptionItem['name_prefix'])) $product->namePrefix = $descriptionItem['name_prefix'];
            if (isset($descriptionItem['tagline'])) $product->tagline = (string)$descriptionItem['tagline'];
            if (isset($descriptionItem['description'])) $product->description = (string)$descriptionItem['description'];

            // trustfactors
            if (isset($descriptionItem['trustfactors']) && is_array($descriptionItem['trustfactors'])) {
                foreach ($descriptionItem['trustfactors'] as $trustfactorItem) {
                    if (!isset($trustfactorItem['uid'])) continue;

                    $product->trustfactors[] = new Model\Product\Trustfactor($trustfactorItem);
                }
            }

            // property groups
            if (isset($descriptionItem['property_groups'][0])) {
                foreach ($descriptionItem['property_groups'] as $propertyGroupItem) {
                    if (!isset($propertyGroupItem['uid'])) continue;

                    $product->propertyGroups[] = new Model\Product\Property\Group($propertyGroupItem);
                }
            }

            // property
            if (isset($descriptionItem['properties'][0])) {
                foreach ($descriptionItem['properties'] as $propertyItem) {
                    if (!isset($propertyItem['uid'])) continue;

                    $product->properties[] = new Model\Product\Property($propertyItem);
                }
            }

            $product->media = new Model\MediaList(isset($descriptionItem['medias']) ? $descriptionItem['medias'] : []);

            $hasAffectOldPriceLabel = false;
            if (!empty($descriptionItem['label']['medias'])) {
                foreach ($descriptionItem['label']['medias'] as $mediaItem) {
                    if ('image' === $mediaItem['provider']) {
                        $product->labels[] = new Model\Product\Label($descriptionItem['label']);

                        if ($descriptionItem['label']['affects_price']) {
                            $hasAffectOldPriceLabel = true;
                        }

                        break;
                    }
                }
            }

            // Т.к. из метода api.enter.ru/v2/product/get-v3 была убрана связь между выводом старой цены и наличием
            // шильдика, реализуем эту связь пока здесь (подробности в CORE-2936)
            if (!$hasAffectOldPriceLabel) {
                $product->oldPrice = null;
            }

            if (!empty($descriptionItem['brand']['medias']) && isset($descriptionItem['brand']['slug']) && $descriptionItem['brand']['slug'] === 'tchibo-3569') {
                foreach ($descriptionItem['brand']['medias'] as $mediaItem) {
                    if ('image' === $mediaItem['provider']) {
                        $product->brand = new Model\Brand($descriptionItem['brand']);
                        // TODO после решения FCMS-740 удалить данный блок (чтобы media бралось из scms) и удалить условие "isset($descriptionItem['brand']['slug']) && $descriptionItem['brand']['slug'] === 'tchibo-3569'"
                        $product->brand->media->photos[] = new Model\Media([
                            'content_type' => 'image/png',
                            'provider' => 'image',
                            'tags' => ['product'],
                            'sources' => [
                                [
                                    'type' => 'original',
                                    'url' => 'http://content.enter.ru/wp-content/uploads/2014/05/tchibo.png',
                                    'width' => '40',
                                    'height' => '40',
                                ],
                            ],
                        ]);
                        break;
                    }
                }
            }

            if (!empty($descriptionItem['categories'])) {
                foreach ($descriptionItem['categories'] as $category) {
                    if ($category['main']) {
                        $product->category = new Model\Product\Category($category);
                    }
                }
            }

            $isSlotPartnerOffer = false;
            foreach ($product->partnerOffers as $partnerOffer) {
                if (2 == $partnerOffer->partner->type) {
                    $isSlotPartnerOffer = true;
                    break;
                }
            }

            if (!$product->isInShopStockOnly && !$product->isInShopShowroomOnly && $product->category && (new \EnterRepository\Product\Category())->getRootObject($product->category)->isFurniture && $product->isStore && !$isSlotPartnerOffer) {
                $product->storeLabel = new \EnterModel\Product\StoreLabel();
                $product->storeLabel->name = 'Товар со склада';
            }

            if (isset($descriptionItem['tags'][0])) {
                foreach ($descriptionItem['tags'] as $tag) {
                    if (!isset($tag['slug'])) continue;

                    if ($tag['slug'] === 'soberi-sam') {
                        $product->assemblingLabel = new \EnterModel\Product\AssemblingLabel();
                        $product->assemblingLabel->name = $tag['name'];
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['repository']]);
        }
    }

    /**
     * @param Model\Product[] $products
     * @param Query[] $modelListQueryList
     */
    public function setModelForListByListQueryList(array $products, array $modelListQueryList) {
        $productsByUi = [];
        foreach ($products as $product) {
            $productsByUi[$product->ui] = $product;
        }

        try {
            foreach ($modelListQueryList as $modelQuery) {
                foreach ($modelQuery->getResult() as $modelItem) {
                    if (!isset($modelItem['uid']) || !isset($productsByUi[$modelItem['uid']])) continue;

                    /** @var Model\Product|null $product */
                    $product = $productsByUi[$modelItem['uid']];

                    if (!empty($modelItem['model']['property']) && !empty($modelItem['model']['items'])) {
                        $product->model = new Model\Product\ProductModel($modelItem['model']);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['repository']]);
        }
    }

    /**
     * @param Model\Product[] $products
     * @param string $id
     * @return Model\Product|null
     */
    public function getObjectFromListById(array $products, $id) {
        foreach ($products as $product) {
            if ($product->id === $id) {
                return $product;
            }
        }

        return null;
    }

    /**
     * @param string[] $productIds
     * @param Model\Product[] $productsById
     */
    public function filterByStockStatus(array &$productIds, array $productsById) {
        $productIds = array_values(array_filter($productIds, function($productId) use($productsById) {
            if (!isset($productsById[$productId])) {
                return null;
            }

            return $productsById[$productId]->isBuyable;
        }));
    }

    /**
     * @param Model\Product[] $products
     */
    public function filterObjectListByStockStatus(array &$products) {
        $products = array_filter($products, function(Model\Product $product) {
            return $product->isBuyable;
        });
    }

    /**
     * @param string[] $productIds
     * @param Model\Product[] $productsById
     * @param bool $randomize
     */
    public function sortByStockStatus(array &$productIds, array $productsById, $randomize = true) {
        try {
            usort($productIds, function($aId, $bId) use (&$productsById, &$randomize) {
                /** @var \EnterModel\Product|null $a */
                $a = isset($productsById[$aId]) ? $productsById[$aId] : null;
                /** @var \EnterModel\Product|null $b */
                $b = isset($productsById[$bId]) ? $productsById[$bId] : null;

                if (!$a || !$b) {
                    return ($b ? 1 : -1) - ($a ? 1 : -1);
                }

                if ($b->isBuyable != $a->isBuyable) {
                    return ($b->isBuyable ? 1 : -1) - ($a->isBuyable ? 1 : -1); // сначала те, которые можно купить
                } else if ($b->isInShopOnly != $a->isInShopOnly) {
                    //return ($b->isInShopOnly ? -1 : 1) - ($a->isInShopOnly ? -1 : 1); // потом те, которые можно зарезервировать
                } else if ($b->isInShopShowroomOnly != $a->isInShopShowroomOnly) {// потом те, которые есть на витрине
                    return ($b->isInShopShowroomOnly ? -1 : 1) - ($a->isInShopShowroomOnly ? -1 : 1);
                } else {
                    return $randomize ? (int)rand(-1, 1) : 0;
                }
            });
        } catch (\Exception $e) {
            $this->logger->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['repository']]);
        }
    }

    /**
     * @param Model\Product[] $products
     * @param Query $favoriteListQuery
     * @throws \Exception
     */
    public function setFavoriteForObjectListByQuery(array $products, Query $favoriteListQuery) {
        // товары по ui
        $productsByUi = [];
        foreach ($products as $product) {
            $productsByUi[$product->ui] = $product;
        }

        foreach ($favoriteListQuery->getResult() as $item) {
            $item += ['uid' => null, 'is_favorite' => null];

            $ui = $item['uid'] ? (string)$item['uid'] : null;
            if (!$ui || !$item['is_favorite'] || !isset($productsByUi[$ui])) continue;

            $productsByUi[$ui]->favorite = [
                'ui' => $ui,
            ]; // FIXME
        }
    }
}