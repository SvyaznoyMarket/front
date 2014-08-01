<?php

namespace EnterMobile\Model\Page\Search {
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

namespace EnterMobile\Model\Page\Search\Index {
    use EnterMobile\Model\Page;
    use EnterMobile\Model\Partial;

    class Content extends Page\DefaultPage\Content {
        /** @var Partial\ProductCatalog\CategoryBlock|null */
        public $categoryBlock;
        /** @var Partial\ProductBlock|null */
        public $productBlock;
        /** @var Partial\ProductFilterBlock|null */
        public $filterBlock;
        /** @var Partial\SelectedFilterBlock|null */
        public $selectedFilterBlock;
        /** @var Partial\SortingBlock|null */
        public $sortingBlock;

        public function __construct() {
            parent::__construct();
        }
    }
}

