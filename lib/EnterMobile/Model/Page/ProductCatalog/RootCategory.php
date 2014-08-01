<?php

namespace EnterMobile\Model\Page\ProductCatalog {
    use EnterMobile\Model\Page;

    class RootCategory extends Page\DefaultPage {
        /** @var RootCategory\Content */
        public $content;

        public function __construct() {
            parent::__construct();

            $this->content = new RootCategory\Content();
        }
    }
}

namespace EnterMobile\Model\Page\ProductCatalog\RootCategory {
    use EnterMobile\Model\Page;
    use EnterMobile\Model\Partial;

    class Content extends Page\DefaultPage\Content {
        /** @var Partial\ProductCatalog\CategoryBlock|null */
        public $categoryBlock;

        public function __construct() {
            parent::__construct();
        }
    }
}

