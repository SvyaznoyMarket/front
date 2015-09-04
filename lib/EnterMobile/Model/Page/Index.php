<?php

namespace EnterMobile\Model\Page {
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

namespace EnterMobile\Model\Page\Index {
    use EnterMobile\Model\Page;
    use EnterMobile\Model\Partial;

    class Content extends Page\DefaultPage\Content {
        /** @var string */
        public $promoDataValue;
        /** @var array */
        public $promos;
        /** @var Partial\ProductSlider|null */
        public $popularSlider;
        /** @var Partial\ProductSlider|null */
        public $personalSlider;
        /** @var Partial\ProductSlider|null */
        public $viewedSlider;
        /** @var array */
        public $popularBrands = [];
        /**
         * Главное меню выше рекомендаций MSITE-489
         * @var bool
         */
        public $mainMenuOnBottom;

        public function __construct() {
            parent::__construct();
        }
    }
}

