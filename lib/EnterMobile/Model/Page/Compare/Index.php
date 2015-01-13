<?php

namespace EnterMobile\Model\Page\Compare {
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

namespace EnterMobile\Model\Page\Compare\Index {
    use EnterMobile\Model\Page;
    use EnterMobile\Model\Partial;

    class Content extends Page\DefaultPage\Content {

        public function __construct() {
            parent::__construct();
        }
    }
}

