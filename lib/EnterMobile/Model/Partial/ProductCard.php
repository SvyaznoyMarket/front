<?php
namespace EnterMobile\Model\Partial {
    use EnterMobile\Model\Partial;

    class ProductCard {
        /** @var string */
        public $name;
        /** @var string */
        public $namePrefix;
        /** @var string */
        public $url;
        /** @var float */
        public $price;
        /** @var string */
        public $shownPrice;
        /** @var float */
        public $oldPrice;
        /** @var string */
        public $shownOldPrice;
        /** @var string */
        public $image;
        /** @var string */
        public $id;
        /** @var string */
        public $categoryId;
        /** @var Partial\Cart\ProductButton|null */
        public $cartButton;
        /** @var Partial\Rating|null */
        public $rating;
        /** @var string */
        public $dataGa;
        /** @var ProductCard\Brand|null */
        public $brand;
        /** @var ProductCard\Label[] */
        public $labels = [];
        /** @var array */
        public $states = [
            'isBuyable'             => false,
            'isInShopOnly'          => false,
            'isInShopStockOnly'     => false,
            'isInShopShowroomOnly'  => false,
            'isInWarehouse'         => false,
            'isKitLocked'           => false
        ];
        /** @var array */
        public $stateLabel = [];
    }
}

namespace EnterMobile\Model\Partial\ProductCard {
    class Label {
        /** @var string */
        public $id;
        /** @var string */
        public $name;
        /** @var string */
        public $imageUrl;
    }

    class Brand {
        /** @var string */
        public $id;
        /** @var string */
        public $name;
        /** @var string */
        public $token;
        /** @var string */
        public $imageUrl;
    }
}