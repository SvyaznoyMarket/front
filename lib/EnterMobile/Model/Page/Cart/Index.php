<?php

namespace EnterMobile\Model\Page\Cart {
    use EnterMobile\Model\Page;

    class Index extends Page\DefaultPage {
        /** @var Index\Content */
        public $content;

        public function __construct() {
            parent::__construct();

            $this->content = new Index\Content();
        }
    }
}

namespace EnterMobile\Model\Page\Cart\Index {
    use EnterMobile\Model\Page;
    use EnterMobile\Model\Partial;

    class Content extends Page\DefaultPage\Content {
        /** @var Content\ProductBlock */
        public $productBlock;
        /** @var Partial\Cart */
        public $cart;
        /** @var string */
        public $orderUrl;
        /** @var string */
        public $orderDataGa;

        public function __construct() {
            parent::__construct();

            $this->productBlock = new Content\ProductBlock();
            $this->cart = new Partial\Cart();
        }
    }
}

namespace EnterMobile\Model\Page\Cart\Index\Content {
    use EnterMobile\Model\Partial;

    class ProductBlock {
        /** @var Partial\Cart\ProductCard[] */
        public $products = [];
    }
}