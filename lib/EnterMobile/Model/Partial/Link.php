<?php

namespace EnterMobile\Model\Partial {
    use EnterMobile\Model\Partial;

    class Link extends Partial\Widget {
        public $widgetType = 'link';
        /** @var string */
        public $name;
        /** @var string */
        public $url;
    }
}
