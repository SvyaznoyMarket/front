<?php

namespace EnterMobile\Model\Page\Order {
    use EnterMobile\Model\Page;

    class Complete extends Page\DefaultPage {
        /** @var Complete\Content */
        public $content;
        /** @var array */
        public $steps = [];

        public function __construct() {
            parent::__construct();

            $this->content = new Complete\Content();
        }
    }
}

namespace EnterMobile\Model\Page\Order\Complete {
    use EnterMobile\Model\Page;
    use EnterMobile\Model\Partial;

    class Content extends Page\DefaultPage\Content {
        /** @var array */
        public $orders = [];
        /** @var bool */
        public $isSingleOrder;

        public function __construct() {
            parent::__construct();
        }
    }
}
