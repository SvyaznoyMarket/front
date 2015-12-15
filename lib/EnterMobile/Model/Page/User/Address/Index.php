<?php

namespace EnterMobile\Model\Page\User\Address {
    use EnterMobile\Model\Page;

    class Index extends Page\User\DefaultPage {
        /** @var Index\Content */
        public $content;

        public function __construct() {
            parent::__construct();

            $this->content = new Index\Content();
        }
    }
}

namespace EnterMobile\Model\Page\User\Address\Index {
    use EnterMobile\Model\Page;
    use EnterMobile\Model\Partial;

    class Content extends Page\User\DefaultPage\Content {
        /** @var array */
        public $addresses = [];

        public function __construct() {
            parent::__construct();
        }
    }
}