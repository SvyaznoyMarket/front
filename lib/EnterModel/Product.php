<?php

namespace EnterModel;

use EnterAggregator\ConfigTrait;
use EnterModel as Model;

class Product {
    use ConfigTrait;

    /** @var string */
    public $id;
    /** @var string */
    public $ui;
    /** @var string */
    public $article;
    /** @var string */
    public $barcode;
    /** @var string */
    public $typeId;
    /** @var string */
    public $webName;
    /** @var string */
    public $namePrefix;
    /** @var string */
    public $name;
    /** @var string */
    public $token;
    /** @var string */
    public $link;
    /** @var string */
    public $description;
    /** @var string */
    public $tagline;
    /** @var bool */
    public $isBuyable;
    /** @var bool */
    public $isInShopOnly;
    /** @var bool */
    public $isInShopStockOnly;
    /** @var bool */
    public $isInShopShowroomOnly;
    /** @var bool */
    public $isInWarehouse;
    /** @var bool */
    public $isKitLocked;
    /** @var Model\Product\Category|null */
    public $category;
    /** @var Model\Brand|null */
    public $brand;
    /** @var Model\Product\Property[] */
    public $properties = [];
    /** @var Model\Product\Property\Group[] */
    public $propertyGroups = [];
    /** @var Model\Product\Stock[] */
    public $stock = [];
    /** @var Model\Product\ShopState[] */
    public $shopStates = [];
    /** @var float */
    public $price;
    /** @var float */
    public $oldPrice;
    /** @var Model\Product\Label[] */
    public $labels = [];
    /** @var Model\Product\Media */
    public $media;
    /** @var Model\Product\Rating|null */
    public $rating;
    /** @var Model\Product\ProductModel|null */
    public $model;
    /** @var Model\Product\Line|null */
    public $line;
    /** @var Model\Product\NearestDelivery[] */
    public $nearestDeliveries = [];
    /** @var string[] */
    public $accessoryIds = [];
    /** @var string[] */
    public $relatedIds = [];
    /** @var Model\Product\Relation */
    public $relation;
    /** @var Model\Product\Kit[] */
    public $kit = [];
    /** @var Model\Product\Review[] */
    public $reviews = [];
    /** @var Model\Product\Trustfactor[] */
    public $trustfactors = [];
    /** @var Model\Product\PartnerOffer[] */
    public $partnerOffers = [];
    /** @var int */
    public $availableStoreQuantity;
    /** @var array|null */
    public $favorite;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        static $photoUrlSizes;

        if (!$photoUrlSizes) {
            $photoUrlSizes = [
                'product_60'   => '/1/1/60/',
                'product_120'  => '/1/1/120/',
                'product_160'  => '/1/1/160/',
                'product_200'  => '/1/1/200/',
                'product_500'  => '/1/1/500/',
                'product_1500' => '/1/1/1500/',
                'product_2500' => '/1/1/2500/',
            ];
        }

        $this->media = new Model\Product\Media();
        $this->relation = new Model\Product\Relation();

        if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
        if (array_key_exists('ui', $data)) $this->ui = (string)$data['ui'];
        if (array_key_exists('article', $data)) $this->article = (string)$data['article'];
        if (array_key_exists('bar_code', $data)) $this->barcode = (string)$data['bar_code'];
        if (array_key_exists('type_id', $data)) $this->typeId = (string)$data['type_id'];
        if (array_key_exists('name', $data)) $this->name = (string)$data['name'];
        if (array_key_exists('prefix', $data)) $this->namePrefix = (string)$data['prefix'];
        if (array_key_exists('name_web', $data)) $this->webName = $data['name_web'] ? (string)$data['name_web'] : null;
        if (array_key_exists('token', $data)) $this->token = (string)$data['token'];
        if (array_key_exists('link', $data)) $this->link = rtrim((string)$data['link'], '/');
        if (array_key_exists('description', $data)) $this->description = (string)$data['description'];
        if (array_key_exists('tagline', $data)) $this->tagline = (string)$data['tagline'];
        if (array_key_exists('price', $data)) $this->price = $data['price'] ? (float)$data['price'] : null;
        if (array_key_exists('price_old', $data)) $this->oldPrice = $data['price_old'] ? (float)$data['price_old'] : null;
        if (array_key_exists('is_kit_locked', $data)) $this->isKitLocked = (bool)$data['is_kit_locked'];

        $this->isBuyable = isset($data['state']['is_buyable']) && (bool)$data['state']['is_buyable'];
        $this->calculateState(
            isset($data['stock'][0]) ? $data['stock'] : [],
            isset($data['partners_offer'][0]) ? $data['partners_offer'] : []
        );

        if (isset($data['category'][0])) {
            $categoryItem = (array)array_pop($data['category']);
            $this->category = new Model\Product\Category($categoryItem);

            foreach ($data['category'] as $categoryItem) {
                if (empty($categoryItem['id'])) continue;
                $this->category->ascendants[] = new Model\Product\Category($categoryItem);
            }
        }

        if (isset($data['brand']['id'])) $this->brand = new Model\Brand($data['brand']);

        if (isset($data['property'][0])) {
            foreach ($data['property'] as $propertyItem) {
                $this->properties[] = new Model\Product\Property((array)$propertyItem);
            }
        }

        if (isset($data['property_group'][0])) {
            foreach ($data['property_group'] as $propertyGroupItem) {
                $this->propertyGroups[] = new Model\Product\Property\Group((array)$propertyGroupItem);
            }
        }

        // ядерные фотографии
        if (isset($data['media'][0])) {
            call_user_func(function() use (&$data, &$photoUrlSizes) {
                // host
                $hosts = $this->getConfig()->mediaHosts;
                $index = !empty($photoId) ? ($photoId % count($hosts)) : rand(0, count($hosts) - 1);
                $host = isset($hosts[$index]) ? $hosts[$index] : '';

                foreach ($data['media'] as $mediaItem) {
                    if (!$mediaItem['source'] || $mediaItem['type_id'] != 1) continue;
                    // преобразование в формат scms
                    $item = [
                        'content_type' => 'image/jpeg',
                        'provider'     => 'image',
                        'tags'         => [],
                        'sources'      => [],
                    ];
                    foreach ($photoUrlSizes as $type => $prefix) {
                        $item['sources'][] = [
                            'type' => $type,
                            'url'  => $host . $prefix . $mediaItem['source'],
                        ];
                    }

                    $this->media->photos[] = $media = new Model\Media($item);
                }
            });
        }

        if (isset($data['label'][0])) {
            foreach ($data['label'] as $labelItem) {
                $this->labels[] = new Model\Product\Label($labelItem);
            }
        }

        if (isset($data['stock'][0])) {
            foreach ($data['stock'] as $stockItem) {
                $this->stock[] = new Model\Product\Stock((array)$stockItem);
            }
        }

        if (isset($data['kit'][0])) {
            foreach ($data['kit'] as $kitItem) {
                if (empty($kitItem['id'])) continue;
                $this->kit[] = new Model\Product\Kit((array)$kitItem);
            }
        }

        if (isset($data['model']['property'][0])) $this->model = new Model\Product\ProductModel($data['model']);
        if (isset($data['line']['id'])) $this->line = new Model\Product\Line($data['line']);
        if (isset($data['accessories'][0])) $this->accessoryIds = $data['accessories'];
        if (isset($data['related'][0])) $this->relatedIds = $data['related'];

        if (isset($data['partners_offer'][0])) {
            foreach ($data['partners_offer'] as $partnerOffer) {
                $partnerOffer = new Model\Product\PartnerOffer((array)$partnerOffer);
                // Пока не требуется отдавать предложения других партнёров
                if (Model\Product\Partner::TYPE_SLOT == $partnerOffer->partner->type) {
                    $this->partnerOffers[] = $partnerOffer;
                }
            }
        }
    }

    /**
     * @param array $stockData
     * @param array $partnerData
     */
    protected function calculateState(array $stockData, array $partnerData) {
        $this->isInWarehouse = false;
        $inWarehouse = false;
        $inShowroom = false;
        $inShop = false;

        $availableStories = [];

        foreach ($stockData as $stockItem) {
            if ($stockItem['store_id'] && $stockItem['quantity']) { // есть на центральном складе
                $inWarehouse = true;
                $this->isInWarehouse = true;
            }
            if ($stockItem['shop_id'] && $stockItem['quantity']) { // есть на складе магазина
                $inShop = true;
            }
            if ($stockItem['shop_id'] && $stockItem['quantity_showroom']) { // есть на витрине магазина
                $inShowroom = true;
            }

            // TERMINALS-1050
            if ($stockItem['store_id'] && $stockItem['quantity']) {
                $availableStories[] = $stockItem;
            }
        }

        usort($availableStories, function($a, $b) {
            return $b['priority'] - $a['priority'];
        });
        $availableStore = reset($availableStories) ?: null;
        if ($availableStore) {
            $this->availableStoreQuantity =
                ($availableStore['is_supplier'] && !$availableStore['quantity'])
                ? (
                    $availableStore['is_infinite']
                    ? $availableStore['quantity']
                    : $availableStore['quantity_supplier']
                )
                : $availableStore['quantity']
            ;
        }

        // TERMINALS-947
        foreach ($partnerData as $partnerItem) {
            $partnerItem += ['stock' => []];
            foreach ((array)$partnerItem['stock'] as $stockItem) {
                if (!empty($stockItem['quantity'])) {
                    $this->isInWarehouse = true;
                    break;
                }
            }
        }

        $this->isInShopOnly = !$inWarehouse && ($inShop || $inShowroom); // не на центральном складе, на складе магазина или на витрине магазина
        $this->isInShopStockOnly = !$inWarehouse && $inShop && !$inShowroom; // не на центральном складе, на складе магазина, не на витрине магазина
        $this->isInShopShowroomOnly = !$inWarehouse && !$inShop && $inShowroom; // не на центральном складе, не на складе магазина, на витрине магазина
    }

    /**
     * @return Model\Product\PartnerOffer|null
     */
    public function getSlotPartnerOffer()
    {
        foreach ($this->partnerOffers as $offer) {
            if (2 == $offer->partner->type) {
                return $offer;
            }
        }

        return null;
    }
}