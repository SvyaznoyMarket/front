<?php

namespace EnterMobile\Model\Page\User {
    use EnterMobile\Model\Page;

    class EnterprizeCoupon extends Page\User\DefaultPage {
        /** @var EnterprizeCoupon\Content */
        public $content;

        public function __construct() {
            parent::__construct();

            $this->content = new EnterprizeCoupon\Content();
        }
    }
}

namespace EnterMobile\Model\Page\User\EnterprizeCoupon {
    use EnterMobile\Model\Page;
    use EnterMobile\Model\Partial;

    class Content extends Page\User\DefaultPage\Content {
        /** @var array */
        public $coupon;

        public function __construct() {
            parent::__construct();
        }
    }
}