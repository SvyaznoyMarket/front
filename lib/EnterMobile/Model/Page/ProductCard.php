<?php

namespace EnterMobile\Model\Page {
    use EnterMobile\Model\Page;

    class ProductCard extends Page\DefaultPage {
        /** @var ProductCard\Content */
        public $content;

        public function __construct() {
            parent::__construct();

            $this->content = new ProductCard\Content();
        }
    }
}

namespace EnterMobile\Model\Page\ProductCard {
    use EnterMobile\Model\Page;

    class Content extends Page\DefaultPage\Content {
        /** @var Content\Product */
        public $product;

        public function __construct() {
            parent::__construct();

            $this->product = new Content\Product();
        }
    }
}

namespace EnterMobile\Model\Page\ProductCard\Content {
    use EnterMobile\Model\Partial;

    class Product {
        /** @var string */
        public $name;
        /** @var string */
        public $namePrefix;
        /** @var string */
        public $article;
        /** @var int */
        public $price;
        /** @var string */
        public $shownPrice;
        /** @var int */
        public $oldPrice;
        /** @var string */
        public $shownOldPrice;
        /** @var Partial\ProductCard\CartButtonBlock|null */
        public $cartButtonBlock;
        /** @var Product\DeliveryBlock|null */
        public $deliveryBlock;
        /** @var Product\ShopStateBlock|null */
        public $shopStateBlock;
        /** @var string */
        public $description;
        /** @var Product\Photo|null */
        public $mainPhoto;
        /** @var Product\Photo[] */
        public $photos = [];
        /** @var bool */
        public $hasVideo;
        /** @var Product\Video[] */
        public $videos = [];
        /** @var bool */
        public $hasPhoto3d;
        /** @var Product\Photo3d[] */
        public $photo3ds = [];
        /** @var Product\PropertyChunk[] */
        public $propertyChunks = [];
        /** @var Partial\Rating|null */
        public $rating;
        /** @var Product\KitBlock|null */
        public $kitBlock;
        /** @var Partial\ProductSlider|null */
        public $accessorySlider;
        /** @var Partial\ProductSlider|null */
        public $alsoBoughtSlider;
        /** @var Partial\ProductSlider|null */
        public $alsoViewedSlider;
        /** @var Partial\ProductSlider|null */
        public $similarSlider;
        /** @var Product\ReviewBlock|null */
        public $reviewBlock;
        /** @var bool */
        public $hasModel;
        /** @var Product\ModelBlock|null */
        public $modelBlock;
        /** @var Product\ModelBlock|null */
        public $moreModelBlock;
        /** @var Partial\DirectCredit|null */
        public $credit;

        public function __construct() {}
    }
}

namespace EnterMobile\Model\Page\ProductCard\Content\Product {
    use EnterMobile\Model\Partial;

    class DeliveryBlock {
        /** @var DeliveryBlock\Delivery[] */
        public $deliveries = [];
    }

    class ShopStateBlock {
        /** @var string */
        public $shownCount;
        /** @var ShopStateBlock\State[] */
        public $states = [];
        /**
         * Только один магазин
         *
         * @var bool
         */
        public $hasOnlyOne;
    }

    class Photo {
        /** @var string */
        public $name;
        /** @var string */
        public $url;
        /** @var string */
        public $previewUrl;
        /** @var string */
        public $originalUrl;
    }

    class Video {
        /** @var string */
        public $content;
    }

    class Photo3d {
        /** @var string */
        public $source;
    }

    class PropertyChunk {
        /** @var PropertyChunk\Property[] */
        public $properties = [];
    }

    class KitBlock {
        /** @var KitBlock\Product[] */
        public $products = [];
        /** @var string */
        public $shownQuantity;
        /** @var string */
        public $shownSum;
        /** @var Partial\Cart\ProductButton|null */
        public $cartButton;
        /** @var bool */
        public $isLocked;
    }

    class ReviewBlock {
        /** @var ReviewBlock\Review[] */
        public $reviews = [];
    }

    class ModelBlock {
        /** @var ModelBlock\Property[] */
        public $properties = [];
        /** @var bool */
        public $isImage;
    }
}

namespace EnterMobile\Model\Page\ProductCard\Content\Product\DeliveryBlock {
    class Delivery {
        /** @var string */
        public $token;
        /** @var string */
        public $name;
        /** @var string */
        public $priceText;
        /** @var string */
        public $deliveredAtText;
    }
}

namespace EnterMobile\Model\Page\ProductCard\Content\Product\ShopStateBlock {
    use EnterMobile\Model\Partial;

    class State {
        /** @var string */
        public $name;
        /** @var string */
        public $regime;
        /** @var string */
        public $address;
        /** @var string */
        public $url;
        /** @var array */
        public $subway;
        /** @var bool */
        public $isInShowroomOnly;
        /** @var Partial\Cart\ProductButton|null */
        public $cartButton;
    }
}

namespace EnterMobile\Model\Page\ProductCard\Content\Product\DeliveryBlock\Delivery {
    class Shop {
        /** @var string */
        public $name;
        /** @var string */
        public $url;
    }
}

namespace EnterMobile\Model\Page\ProductCard\Content\Product\PropertyChunk {
    class Property {
        /** @var string */
        public $name;
        /** @var string */
        public $value;
        /** @var bool */
        public $isTitle;
    }
}

namespace EnterMobile\Model\Page\ProductCard\Content\Product\KitBlock {
    use EnterMobile\Model\Partial;

    class Product {
        /** @var string */
        public $name;
        /** @var string */
        public $url;
        /** @var string */
        public $photoUrl;
        /** @var string */
        public $deliveryDate;
        /** @var string */
        public $shownPrice;
        /** @var string */
        public $shownSum;
        /** @var int */
        public $quantity;
        /** @var string */
        public $height;
        /** @var string */
        public $width;
        /** @var string */
        public $depth;
        /** @var string */
        public $unit;
        /** @var Partial\Cart\ProductSpinner|null */
        public $cartSpinner;
    }
}

namespace EnterMobile\Model\Page\ProductCard\Content\Product\ReviewBlock {
    use EnterMobile\Model\Partial;

    class Review {
        /** @var string */
        public $createdAt;
        /** @var string */
        public $author;
        /** @var Partial\Rating\Star[] */
        public $stars = [];
        /** @var string */
        public $extract;
        /** @var string */
        public $pros;
        /** @var string */
        public $cons;
    }
}

namespace EnterMobile\Model\Page\ProductCard\Content\Product\ModelBlock {
    class Property {
        /** @var string */
        public $name;
        /** @var bool */
        public $isImage;
        /** @var Property\Option[] */
        public $options = [];
    }
}

namespace EnterMobile\Model\Page\ProductCard\Content\Product\ModelBlock\Property {
    class Option {
        /** @var string */
        public $shownValue;
        /** @var string */
        public $url;
        /** @var bool */
        public $isActive;
        /** @var string */
        public $image;
        /** @var string */
        public $unit;
    }
}