<?php

namespace EnterMobile\Model\Page\User {
    use EnterMobile\Model\Page;

    class Order extends Page\User\DefaultPage {
        /** @var Index\Content */
        public $content;

        public function __construct() {
            parent::__construct();

            $this->content = new Order\Content();
        }
    }
}

namespace EnterMobile\Model\Page\User\Order {
    use EnterMobile\Model\Page;
    use EnterMobile\Model\Partial;

    class Content extends Page\User\DefaultPage\Content {
        /** @var array */
        public $order;

        public function __construct() {
            parent::__construct();
        }
    }
}