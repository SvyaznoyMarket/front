<?php

namespace EnterModel\Product\Category {
    class Config {
        /** @var Config\BannerPlaceholder */
        public $bannerPlaceholder;
        /** @var string */
        public $listingStyle;
        /** @var array */
        public $accessoryCategoryTokens = [];
        /** @var array */
        public $sortings = [];

        /**
         * @param array $data
         */
        public function __construct(array $data = []) {
            if (isset($data['property']['bannerPlaceholder']) && is_array($data['property']['bannerPlaceholder'])) $this->bannerPlaceholder = new Config\BannerPlaceholder($data['property']['bannerPlaceholder']);
            if (isset($data['property']['appearance']['default']['listing_style'])) $this->listingStyle = (string)$data['property']['appearance']['default']['listing_style'];
            if (isset($data['property']['products']['accessory_category_token'][0])) {
                foreach (array_unique($data['property']['products']['accessory_category_token']) as $accessoryCategoryToken) {
                    if (!is_scalar($accessoryCategoryToken)) continue;
                    $this->accessoryCategoryTokens[] = trim((string)$accessoryCategoryToken);
                }
            }
            if (isset($data['property']['sort']['json']) && is_array($data['property']['sort']['json'])) {
                foreach ($data['property']['sort']['json'] as $sortingName => $sortingItem) {
                    if (!$sortingName) continue;

                    $this->sortings[$sortingName] = $sortingItem;
                }
            }
        }
    }
}

namespace EnterModel\Product\Category\Config {

    class BannerPlaceholder {
        /** @var int */
        public $position;
        /** @var string */
        public $image;

        /**
         * @param array $data
         */
        public function __construct(array $data = []) {
            if (isset($data['position'])) $this->position = (int)$data['position'];
            if (isset($data['image'])) $this->image = (string)$data['image'];
        }
    }
}
