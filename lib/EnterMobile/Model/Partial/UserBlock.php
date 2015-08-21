<?php

namespace EnterMobile\Model\Partial {
    use EnterMobile\Model\Partial;

    class UserBlock extends Partial\Widget {
        public $widgetType = 'userBlock';
        /** @var bool */
        public $isUserAuthorized;
        /** @var bool */
        public $isCartNotEmpty;
        /** @var bool */
        public $isEnterprizeMember;
        /** @var Partial\Link */
        public $userLink;
        /** @var UserBlock\Cart */
        public $cart;

        public function __construct() {
            $this->widgetId = 'id-userBlock';
            $this->userLink = new Partial\Link();
            $this->cart = new UserBlock\Cart();
        }
    }
}

namespace EnterMobile\Model\Partial\UserBlock {
    class Cart {
        /** @var string */
        public $url;
        /** @var int */
        public $quantity;
        /** @var float */
        public $sum;
        /** @var string */
        public $shownSum;
    }
}