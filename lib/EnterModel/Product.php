<?php

namespace EnterModel {
    use EnterAggregator\ConfigTrait;
    use EnterModel as Model;

    class Product {
        use ConfigTrait; // FIXME

        /** @var string */
        public $id = '';
        /** @var string */
        public $ui = '';
        /** @var string */
        public $article = '';
        /** @var string */
        public $barcode = '';
        /** @var string */
        public $typeId = '';
        /** @var string */
        public $webName = '';
        /** @var string */
        public $namePrefix = '';
        /** @var string */
        public $name = '';
        /** @var string */
        public $token = '';
        /** @var string */
        public $link = '';
        /** @var string */
        public $description = '';
        /** @var string */
        public $tagline = '';
        /** @var bool|null */
        public $isBuyable;
        /** @var bool|null */
        public $isInShopOnly;
        /** @var bool|null */
        public $isInShopStockOnly;
        /** @var bool|null */
        public $isInShopShowroomOnly;
        /** @var bool|null */
        public $isInWarehouse;
        /** @var bool|null */
        public $isKitLocked;
        /** @var int|null */
        public $kitCount;
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
        /** @var float|null */
        public $price;
        /** @var float|null */
        public $oldPrice;
        /** @var Model\Product\Label[] */
        public $labels = [];
        /** @var Model\MediaList */
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
        /** @var int|null */
        public $availableStoreQuantity;
        /** @var array|null */
        public $favorite;
        /** @var array|null */
        public $sender;
        /** @var array|null */
        public $ga;
        /** @var bool|null */
        public $isStore;
        /** @var \EnterModel\Product\StoreLabel|null Метка "Товар со склада" */
        public $storeLabel;
        /** @var \EnterModel\Product\AssemblingLabel|null Метка "Собери сам" */
        public $assemblingLabel;

        /**
         * @param array $data
         */
        public function __construct(array $data = []) {
            $this->media = new Model\MediaList();
            $this->relation = new Model\Product\Relation();

            if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
            if (array_key_exists('ui', $data)) $this->ui = (string)$data['ui'];
            if (array_key_exists('uid', $data)) $this->ui = (string)$data['uid'];
            if (array_key_exists('article', $data)) $this->article = (string)$data['article'];
            if (array_key_exists('bar_code', $data)) $this->barcode = (string)$data['bar_code'];
            if (array_key_exists('type_id', $data)) $this->typeId = (string)$data['type_id'];
            if (array_key_exists('price', $data)) $this->price = $data['price'] ? (float)$data['price'] : null;
            if (array_key_exists('price_old', $data)) $this->oldPrice = $data['price_old'] ? (float)$data['price_old'] : null;
            if (array_key_exists('is_kit_locked', $data)) $this->isKitLocked = (bool)$data['is_kit_locked'];

            $this->isBuyable = isset($data['state']['is_buyable']) && (bool)$data['state']['is_buyable'];
            $this->calculateState(
                isset($data['stock'][0]) ? $data['stock'] : [],
                isset($data['partners_offer'][0]) ? $data['partners_offer'] : []
            );

            if (isset($data['properties'][0])) {
                foreach ($data['properties'] as $propertyItem) {
                    $this->properties[] = new Model\Product\Property((array)$propertyItem);
                }
            }

            if (isset($data['property_groups'][0])) {
                foreach ($data['property_groups'] as $propertyGroupItem) {
                    $this->propertyGroups[] = new Model\Product\Property\Group((array)$propertyGroupItem);
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

            if (isset($data['line']['id'])) $this->line = new Model\Product\Line($data['line']);
            if (isset($data['accessories'][0])) $this->accessoryIds = $data['accessories'];
            if (isset($data['related'][0])) $this->relatedIds = $data['related'];

            if (isset($data['partners_offer'][0])) {
                foreach ($data['partners_offer'] as $partnerOffer) {
                    $partnerOffer = new Model\Product\PartnerOffer((array)$partnerOffer);
                    $this->partnerOffers[] = $partnerOffer;
                }
            }

            if (array_key_exists('state', $data)) {
                $this->isStore = (isset($data['state']['is_store'])) ? $data['state']['is_store'] : false;
            }

        }

        /**
         * @param array $data
         */
        public function fromArray(array $data) {
            if (isset($data['id'])) $this->id = (string)$data['id'];
            if (isset($data['ui'])) $this->ui = (string)$data['ui'];
            if (isset($data['article'])) $this->article = (string)$data['article'];
            if (isset($data['barcode'])) $this->barcode = (string)$data['barcode'];
            if (isset($data['typeId'])) $this->typeId = (string)$data['typeId'];
            if (isset($data['webName'])) $this->webName = (string)$data['webName'];
            if (isset($data['namePrefix'])) $this->namePrefix = (string)$data['namePrefix'];
            if (isset($data['name'])) $this->name = (string)$data['name'];
            if (isset($data['token'])) $this->token = (string)$data['token'];
            if (isset($data['link'])) $this->link = (string)$data['link'];
            if (isset($data['description'])) $this->description = (string)$data['description'];
            if (isset($data['tagline'])) $this->tagline = (string)$data['tagline'];
            if (isset($data['isBuyable'])) $this->isBuyable = (bool)$data['isBuyable'];
            if (isset($data['isInShopOnly'])) $this->isInShopOnly = (bool)$data['isInShopOnly'];
            if (isset($data['isInShopStockOnly'])) $this->isInShopStockOnly = (bool)$data['isInShopStockOnly'];
            if (isset($data['isInShopShowroomOnly'])) $this->isInShopShowroomOnly = (bool)$data['isInShopShowroomOnly'];
            if (isset($data['isInWarehouse'])) $this->isInWarehouse = (bool)$data['isInWarehouse'];
            if (isset($data['isKitLocked'])) $this->isKitLocked = (bool)$data['isKitLocked'];
            if (isset($data['kitCount'])) $this->kitCount = (int)$data['kitCount'];
            if (isset($data['category'])) {
                $this->category = new Model\Product\Category();
                $this->category->fromArray($data['category']);
            }
//            if (isset($data['brand'])) {
//                $this->brand = new Model\Brand();
//                $this->brand->fromArray($data['brand']);
//            }
//            if (isset($data['properties'][0])) {
//                foreach ($data['properties'] as $item) {
//                    $entity = new Model\Product\Property();
//                    $entity->fromArray($item);
//                    $this->properties[] = $entity;
//                }
//            }
//            if (isset($data['propertyGroups'][0])) {
//                foreach ($data['propertyGroups'] as $item) {
//                    $entity = new Model\Product\Property\Group();
//                    $entity->fromArray($item);
//                    $this->propertyGroups[] = $entity;
//                }
//            }
//            if (isset($data['stock'][0])) {
//                foreach ($data['stock'] as $item) {
//                    $entity = new Model\Product\Stock();
//                    $entity->fromArray($item);
//                    $this->stock[] = $entity;
//                }
//            }
//            if (isset($data['shopStates'][0])) {
//                foreach ($data['shopStates'] as $item) {
//                    $entity = new Model\Product\ShopState();
//                    $entity->fromArray($item);
//                    $this->shopStates[] = $entity;
//                }
//            }
//            if (isset($data['price'])) $this->price = (float)$data['price'];
//            if (isset($data['oldPrice'])) $this->oldPrice = (float)$data['oldPrice'];
//            if (isset($data['labels'][0])) {
//                foreach ($data['labels'] as $item) {
//                    $entity = new Model\Product\Label();
//                    $entity->fromArray($item);
//                    $this->labels[] = $entity;
//                }
//            }
            if (isset($data['media'])) {
                $this->media = new Model\MediaList();
                $this->media->fromArray($data['media']);
            }
//            if (isset($data['rating'])) {
//                $this->rating = new Model\Product\Rating();
//                $this->rating->fromArray($data['rating']);
//            }
//            if (isset($data['model'])) {
//                $this->model = new Model\Product\ProductModel();
//                $this->model->fromArray($data['model']);
//            }
//            if (isset($data['line'])) {
//                $this->line = new Model\Product\Line();
//                $this->line->fromArray($data['line']);
//            }
//            if (isset($data['nearestDeliveries'][0])) {
//                foreach ($data['nearestDeliveries'] as $item) {
//                    $entity = new Model\Product\NearestDelivery();
//                    $entity->fromArray($item);
//                    $this->nearestDeliveries[] = $entity;
//                }
//            }
//            if (isset($data['accessoryIds'][0])) $this->accessoryIds = $data['accessoryIds'];
//            if (isset($data['relatedIds'][0])) $this->relatedIds = $data['relatedIds'];
//            if (isset($data['relation'])) {
//                $this->relation = new Model\Product\Relation();
//                $this->relation->fromArray($data['relation']);
//            }
//            if (isset($data['kit'][0])) {
//                foreach ($data['kit'] as $item) {
//                    $entity = new Model\Product\Kit();
//                    $entity->fromArray($item);
//                    $this->kit[] = $entity;
//                }
//            }
//            if (isset($data['reviews'][0])) {
//                foreach ($data['reviews'] as $item) {
//                    $entity = new Model\Product\Review();
//                    $entity->fromArray($item);
//                    $this->reviews[] = $entity;
//                }
//            }
//            if (isset($data['trustfactors'][0])) {
//                foreach ($data['trustfactors'] as $item) {
//                    $entity = new Model\Product\Trustfactor();
//                    $entity->fromArray($item);
//                    $this->trustfactors[] = $entity;
//                }
//            }
//            if (isset($data['partnerOffers'][0])) {
//                foreach ($data['partnerOffers'] as $item) {
//                    $entity = new Model\Product\PartnerOffer();
//                    $entity->fromArray($item);
//                    $this->partnerOffers[] = $entity;
//                }
//            }
//            if (isset($data['availableStoreQuantity'])) $this->availableStoreQuantity = (int)$data['availableStoreQuantity'];
//            if (isset($data['favorite'][0])) $this->favorite = $data['favorite'];
//            if (isset($data['sender'][0])) $this->sender = $data['sender'];
//            if (isset($data['ga'][0])) $this->ga = $data['ga'];
//            if (isset($data['isStore'])) $this->isStore = (bool)$data['isStore'];
//            if (isset($data['storeLabel'])) {
//                $this->storeLabel = new Model\Product\StoreLabel();
//                $this->storeLabel->fromArray($data['storeLabel']);
//            }
//            if (isset($data['assemblingLabel'])) {
//                $this->assemblingLabel = new Model\Product\AssemblingLabel();
//                $this->assemblingLabel->fromArray($data['assemblingLabel']);
//            }
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

        /**
         * @return Model\Product\PartnerOffer|null
         */
        public function getPartnerOffer()
        {
            foreach ($this->partnerOffers as $offer) {
                return $offer;
            }

            return null;
        }
    }
}

namespace EnterModel\Product {
    class StoreLabel {
        /** @var string */
        public $name;
    }
    
    class AssemblingLabel {
        /** @var string */
        public $name;
    }
}