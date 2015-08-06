<?php

namespace EnterMobile\Model\Page\User {
    use EnterMobile\Model\Page;

    class EnterprizeList extends Page\User\DefaultPage {
        /** @var EnterprizeList\Content */
        public $content;

        public function __construct() {
            parent::__construct();

            $this->content = new EnterprizeList\Content();
        }
    }
}

namespace EnterMobile\Model\Page\User\EnterprizeList {
    use EnterMobile\Model\Page;
    use EnterMobile\Model\Partial;

    class Content extends Page\User\DefaultPage\Content {
        /** @var array */
        public $coupons;

        public function __construct() {
            parent::__construct();
        }
    }
}