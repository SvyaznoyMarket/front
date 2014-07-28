<?php

namespace EnterMobile\Model\Page {
    use EnterMobile\Model\Page;

    class Index extends Page\DefaultLayout {
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

    class Content extends Page\DefaultLayout\Content {
        /** @var string */
        public $promoDataValue;

        public function __construct() {
            parent::__construct();
        }
    }
}

