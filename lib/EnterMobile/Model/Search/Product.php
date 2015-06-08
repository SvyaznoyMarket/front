<?php

namespace EnterMobile\Model\Search {
    use EnterModel as Model;
    use EnterAggregator\ConfigTrait;

    class Product {
        use ConfigTrait; // FIXME

        /** @var string */
        public $name;
        /** @var string */
        public $link;
        /** @var Model\Product\Media */
        public $image;

        /**
         * @param array $data
         */
        public function __construct(array $data = []) {

            if (array_key_exists('name', $data)) $this->name = $data['name'] ? (string)$data['name'] : null;
            if (array_key_exists('link', $data)) $this->link = $data['link'] ? (string)$data['link'] : null;

            $photoUrlSizes = [
                 'product_60'   => '/1/1/60/'
            ];

            $hosts = $this->getConfig()->mediaHosts;
            $index = !empty($photoId) ? ($photoId % count($hosts)) : rand(0, count($hosts) - 1);
            $host = isset($hosts[$index]) ? $hosts[$index] : '';

            foreach ($photoUrlSizes as $type => $prefix) {
                $this->image = $host . $prefix . $data['image'];
            }
        }
    }
}