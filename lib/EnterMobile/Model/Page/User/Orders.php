<?php

namespace EnterMobile\Model\Page\User {
    use EnterMobile\Model\Page;

    class Orders extends Page\User\DefaultPage {
        /** @var Index\Content */
        public $content;

        public function __construct() {
            parent::__construct();

            $this->content = new Orders\Content();
        }
    }
}

namespace EnterMobile\Model\Page\User\Orders {
    use EnterMobile\Model\Page;
    use EnterMobile\Model\Partial;

    class Content extends Page\User\DefaultPage\Content {
        /** @var array */
        public $orders;

        public function __construct() {
            parent::__construct();
        }
    }
}