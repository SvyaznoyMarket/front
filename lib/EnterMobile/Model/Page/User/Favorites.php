<?php

namespace EnterMobile\Model\Page\User {
    use EnterMobile\Model\Page;

    class Favorites extends Page\User\DefaultPage {
        /** @var Favorites\Content */
        public $content;

        public function __construct() {
            parent::__construct();

            $this->content = new Favorites\Content();
        }
    }
}

namespace EnterMobile\Model\Page\User\Favorites {
    use EnterMobile\Model\Page;
    use EnterMobile\Model\Partial;

    class Content extends Page\User\DefaultPage\Content {
        /** @var Partial\ProductCard[] */
        public $productCards = [];

        public function __construct() {
            parent::__construct();
        }
    }
}

