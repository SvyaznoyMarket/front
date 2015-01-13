<?php

namespace EnterMobile\Model\Page\User {
    use EnterMobile\Model\Partial;

    class Get {
        /** @var Get\User */
        public $user;
        /** @var Get\Cart */
        public $cart;
        /**
         * Виджеты, индексированные по css-селектору
         * @var Partial\Widget[]
         */
        public $widgets = [];

        public function __construct() {
            $this->user = new Get\User();
            $this->user = new Get\Cart();
        }
    }
}

namespace EnterMobile\Model\Page\User\Get {
    class User {
        /** @var string */
        public $id;
        /** @var string */
        public $sessionId;
    }

    class Cart {
        /** @var Cart\Product[] */
        public $products = [];
    }
}

namespace EnterMobile\Model\Page\User\Get\Cart {
    class Product {
        /** @var string */
        public $id;
        /** @var string */
        public $name;
        /** @var int */
        public $price;
        /** @var int */
        public $quantity;
    }
}