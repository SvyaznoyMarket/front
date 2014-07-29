<?php

namespace EnterMobile\Model\Partial\ProductCatalog {
    use EnterMobile\Model\Partial;

    class CategoryBlock {
        /** @var CategoryBlock\Category[] */
        public $categories = [];
    }
}

namespace EnterMobile\Model\Partial\ProductCatalog\CategoryBlock {
    use EnterMobile\Model\Partial;

    class Category {
        /** @var string */
        public $name;
        /** @var string */
        public $image;
        /** @var string */
        public $url;
    }
}