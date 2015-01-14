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
     * @param Query $query
     * @return Model\Product
     */
    public function getObjectByQuery(Query $query) {
        $product = null;

        if ($item = $query->getResult()) {
            $product = new Model\Product($item);
        }

        return $product;
    }

    /**
     * @param Query[] $queries
     * @param callable|null $parser
     * @return Model\Product[]
     */
    public function getIndexedObjectListByQueryList(array $queries, $parser = null) {
        $parser = is_callable($parser) ? $parser : function(&$item) {
            // оптимизация по умолчанию для листинга
            $item['description'] = null;
            $item['property'] = [];
            $item['property_group'] = [];
            $item['media'] = [reset($item['media'])];
        };

        $products = [];

        foreach ($queries as $query) {
            try {
                foreach ($query->getResult() as $item) {
                    $parser($item);

                    $products[(string)$item['id']] = new Model\Product($item);
                }
            } catch (\Exception $e) {
                trigger_error($e, E_USER_ERROR);
            }
        }

        return $products;
    }

    /**
     * @param Model\Product $product
     * @param Query $videoListQuery
     */
    public function setMediaForObjectByQuery(Model\Product $product, Query $videoListQuery) {
        // TODO
    }

    /**
     * @deprecated
     * @param Model\Product $product
     * @param Query $videoListQuery
     */
    public function setVideoForObjectByQuery(Model\Product $product, Query $videoListQuery) {
        try {
            foreach ($videoListQuery->getResult() as $videoItem) {
                if (empty($videoItem['content'])) continue;

                $videoItem['product_id'] = $product->id;
                $product->media->videos[] = new Model\Product\Media\Video($videoItem);
            }
        } catch (\Exception $e) {
            $this->logger->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['repository']]);
        }
    }

    /**
     * @deprecated
     * @param Model\Product $product
     * @param Query $photo3dListQuery
     */
    public function setPhoto3dForObjectByQuery(Model\Product $product, Query $photo3dListQuery) {
        try {
            foreach ($photo3dListQuery->getResult() as $photo3dItem) {
                if (empty($photo3dItem['maybe3d'])) continue;

                $product->media->photo3ds[] = new Model\Product\Media\Photo3d([
                    'product_id' => $product->id,
                    'source'     => $photo3dItem['maybe3d'],
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['repository']]);
        }
    }

    /**
     * @deprecated
     * @param Model\Product[] $productsById
     * @param Query $videoGroupedListQuery
     */
    public function setVideoForObjectListByQuery(array $productsById, Query $videoGroupedListQuery) {
        try {
            foreach ($videoGroupedListQuery->getResult() as $videoItem) {
                if (!isset($productsById[$videoItem['product_id']])) continue;

                $productsById[$videoItem['product_id']]->media->videos[] = new Model\Product\Media\Video($videoItem);
            }
        } catch (\Exception $e) {
            $this->logger->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['repository']]);
        }
    }

    /**
     * @param array $productsById
     * @param Query $descriptionListQuery
     */
    public function setMediaForObjectListByQuery(array $productsById, Query $descriptionListQuery) {
        // TODO
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
     * @param Query $query
     * @return Model\Product\NearestDelivery[]
     */
    public function getDeliveryObjectByListQuery(Query $query) {
        $nearestDeliveries = [];

        try {
            $result = $query->getResult();
        } catch (\Exception $e) {
            return $nearestDeliveries;
        }

        $productData = &$result['product_list'];
        $shopData = &$result['shop_list'];

        $regionData = [];
        foreach ($result['geo_list'] as $regionItem) {
            $regionData[(string)$regionItem['id']] = $regionItem;
        }

        foreach ($productData as $item) {
            $productId = (string)$item['id'];

            if (!isset($item['delivery_mode_list'])) continue;
            foreach ($item['delivery_mode_list'] as $deliveryItem) {
                if (!isset($deliveryItem['date_list']) || !is_array($deliveryItem['date_list'])) continue;

                // FIXME
                if (in_array($deliveryItem['token'], ['now'])) continue;

                $delivery = new Model\Product\NearestDelivery();
                $delivery->productId = $productId;
                $delivery->id = (string)$deliveryItem['id'];
                $delivery->token = (string)$deliveryItem['token'];
                $delivery->price = (int)$deliveryItem['price'];

                /** @var string $date Ближайшая дата доставки */
                $date = reset($deliveryItem['date_list']);
                $date = !empty($date['date']) ? $date['date'] : null;
                $delivery->deliveredAt = $date ? \DateTime::createFromFormat("Y-m-d", $date) : null;

                $day = 0;
                foreach ($deliveryItem['date_list'] as $dateItem) {
                    $day++;
                    if ($day > 7) break;

                    if (in_array($deliveryItem['token'], ['self', 'now'])) {
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

                $nearestDeliveries[] = $delivery;
            }
        }

        return $nearestDeliveries;
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
                if (!isset($productsById[$productId])) continue;

                if (!isset($item['delivery_mode_list'])) continue;
                foreach ($item['delivery_mode_list'] as $deliveryItem) {
                    if (!isset($deliveryItem['date_list']) || !is_array($deliveryItem['date_list'])) continue;

                    // FIXME
                    if (in_array($deliveryItem['token'], ['now'])) continue;

                    $delivery = new Model\Product\NearestDelivery();
                    $delivery->productId = $productId;
                    $delivery->id = (string)$deliveryItem['id'];
                    $delivery->token = (string)$deliveryItem['token'];
                    $delivery->price = (int)$deliveryItem['price'];

                    /** @var string $date Ближайшая дата доставки */
                    $date = reset($deliveryItem['date_list']);
                    $date = !empty($date['date']) ? $date['date'] : null;
                    $delivery->deliveredAt = $date ? \DateTime::createFromFormat("Y-m-d", $date) : null;

                    $day = 0;
                    foreach ($deliveryItem['date_list'] as $dateItem) {
                        $day++;
                        if ($day > 7) break;

                        if (in_array($deliveryItem['token'], ['self', 'now'])) {
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

                    $productsById[$productId]->nearestDeliveries[] = $delivery;
                }
            }
        } catch (\Exception $e) {
            $this->logger->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['repository']]);
        }
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
     */
    public function setAccessoryRelationForObjectListByQuery(array $productsById, Query $accessoryListQuery) {
        try {
            foreach ($productsById as $product) {
                foreach ($accessoryListQuery->getResult() as $accessoryItem) {
                    // оптимизация
                    $accessoryItem['description'] = null;
                    $accessoryItem['property'] = [];
                    $accessoryItem['property_group'] = [];
                    $accessoryItem['media'] = [reset($accessoryItem['media'])];
                    $product->relation->accessories[] = new Model\Product($accessoryItem);
                }
            }
        } catch (\Exception $e) {
            $this->logger->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['repository']]);
        }
    }

    /**
     * @param Model\Product $product
     * @param Query $descriptionItemQuery
     */
    public function setDescriptionForObjectByQuery($product, Query $descriptionItemQuery) {
        try {
            if ($descriptionItem = $descriptionItemQuery->getResult()) {
                foreach ($descriptionItem['trustfactors'] as $trustfactorItem) {
                    if (!isset($trustfactorItem['uid'])) continue;

                    $product->trustfactors[] = new Model\Product\Trustfactor($trustfactorItem);
                }
            }
        } catch (\Exception $e) {
            $this->logger->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['repository']]);
        }
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
                    return ($b->isInShopOnly ? -1 : 1) - ($a->isInShopOnly ? -1 : 1); // потом те, которые можно зарезервировать
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
}