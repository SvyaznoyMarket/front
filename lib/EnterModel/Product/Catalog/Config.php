<?php

namespace EnterModel\Product\Catalog {
    class Config {
        /** @var Config\BannerPlaceholder */
        public $bannerPlaceholder;
        /** @var Config\PromoStyle */
        public $promoStyle;
        /** @var string */
        public $listingStyle;
        /** @var array */
        public $accessoryCategoryTokens = [];
        /** @var array */
        public $sortings = [];

        public function __construct(array $data = []) {
            if (isset($data['bannerPlaceholder']) && is_array($data['bannerPlaceholder'])) $this->bannerPlaceholder = new Config\BannerPlaceholder($data['bannerPlaceholder']);
            if (isset($data['promo_style']) && is_array($data['promo_style'])) $this->promoStyle = new Config\PromoStyle($data['promo_style']);
            if (isset($data['listing_style'])) $this->listingStyle = (string)$data['listing_style'];
            if (isset($data['accessory_category_token'][0])) {
                foreach (array_unique($data['accessory_category_token']) as $accessoryCategoryToken) {
                    if (!is_scalar($accessoryCategoryToken)) continue;
                    $this->accessoryCategoryTokens[] = trim((string)$accessoryCategoryToken);
                }
            }
            if (isset($data['sort']) && is_array($data['sort'])) {
                foreach ($data['sort'] as $sortingName => $sortingItem) {
                    if (!$sortingName) continue;

                    $this->sortings[$sortingName] = $sortingItem;
                }
            }
        }
    }
}

namespace EnterModel\Product\Catalog\Config {

    class BannerPlaceholder {
        /** @var int */
        public $position;
        /** @var string */
        public $image;

        public function __construct(array $data = []) {
            if (isset($data['position'])) $this->position = (int)$data['position'];
            if (isset($data['image'])) $this->image = (string)$data['image'];
        }
    }

    class PromoStyle {
        /** @var string */
        public $bCustomFilter;
        /** @var string */
        public $bTitlePage;
        /** @var string */
        public $bFilterHead;
        /** @var string */
        public $bPopularSection;
        /** @var string */
        public $bCatalogList;
        /** @var string */
        public $bCatalogList__eItem;
        /** @var string */
        public $bRangeSlider;

        public function __construct(array $data = []) {
            if (isset($data['promo_image'])) $this->bCustomFilter = (string)$data['promo_image'];
            if (isset($data['bCustomFilter'])) $this->bCustomFilter = (string)$data['bCustomFilter'];
            if (isset($data['title'])) $this->bTitlePage = (string)$data['title'];
            if (isset($data['bTitlePage'])) $this->bTitlePage = (string)$data['bTitlePage'];
            if (isset($data['bFilterHead'])) $this->bFilterHead = (string)$data['bFilterHead'];
            if (isset($data['bPopularSection'])) $this->bPopularSection = (string)$data['bPopularSection'];
            if (isset($data['bCatalogList'])) $this->bCatalogList = (string)$data['bCatalogList'];
            if (isset($data['bCatalogList__eItem'])) $this->bCatalogList__eItem = (string)$data['bCatalogList__eItem'];
            if (isset($data['bRangeSlider'])) $this->bRangeSlider = (string)$data['bRangeSlider'];
        }
    }
}
