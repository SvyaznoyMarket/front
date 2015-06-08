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
            static $photoUrlSizes;

            if (!$photoUrlSizes) {
                $photoUrlSizes = [
                     'product_60'   => '/1/1/60/'
                ];
            }

            if (array_key_exists('name', $data)) $this->name = $data['name'] ? (string)$data['name'] : null;
            if (array_key_exists('link', $data)) $this->link = $data['link'] ? (string)$data['link'] : null;

            // ядерные фотографии
            call_user_func(function() use (&$data, &$photoUrlSizes) {
                // host
                $hosts = $this->getConfig()->mediaHosts;
                $index = !empty($photoId) ? ($photoId % count($hosts)) : rand(0, count($hosts) - 1);
                $host = isset($hosts[$index]) ? $hosts[$index] : '';

                $item = [];
                foreach ($photoUrlSizes as $type => $prefix) {
                    $item['image'] = $host . $prefix . $data['image'];
                }

                $this->image = $item['image'];
            });
        }
    }
}