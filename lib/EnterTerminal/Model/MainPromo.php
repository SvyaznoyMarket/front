<?php

namespace EnterTerminal\Model {
    use EnterModel as Model;

    class MainPromo {
        /** @var Model\Region */
        public $region;
        /** @var Model\Shop */
        public $shop;
        /** @var \EnterModel\Promo[] */
        public $promos = [];
    }
}
