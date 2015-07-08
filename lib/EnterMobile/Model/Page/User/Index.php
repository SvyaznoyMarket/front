<?php

namespace EnterMobile\Model\Page\User {
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

namespace EnterMobile\Model\Page\User\Index {
    use EnterMobile\Model\Page;
    use EnterMobile\Model\Partial;

    class Content extends Page\DefaultPage\Content {
        /** @var array */
        public $orders;

        public function __construct() {
            parent::__construct();
        }
    }
}