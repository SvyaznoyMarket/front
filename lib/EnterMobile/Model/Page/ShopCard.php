<?php

namespace EnterMobile\Model\Page {
    use EnterMobile\Model\Page;

    class ShopCard extends Page\DefaultPage {
        /** @var Index\Content */
        public $content;
        public $headerSwitchLink;

        public function __construct() {
            parent::__construct();

            $this->content = new ShopCard\Content();
        }
    }
}

namespace EnterMobile\Model\Page\ShopCard {
    use EnterMobile\Model\Page;
    use EnterMobile\Model\Partial;

    class Content extends Page\DefaultPage\Content {

        public $pointDescription;

        public function __construct() {
            parent::__construct();
        }
    }
}

