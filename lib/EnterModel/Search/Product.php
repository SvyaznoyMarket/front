<?php

namespace EnterModel\Search {
    use EnterModel as Model;
    use EnterAggregator\ConfigTrait;

    class Product {
        use ConfigTrait; // FIXME

        /** @var string */
        public $id;
        /** @var string */
        public $token;
        /** @var string */
        public $name;
        /** @var string */
        public $link;
        /** @var Model\MediaList */
        public $media;

        /**
         * @param array $data
         */
        public function __construct(array $data = []) {
            static $photoUrlSizes;

            if (!$photoUrlSizes) {
                $photoUrlSizes = [
                    //'product_60'   => '/1/1/60/',
                    //'product_120'  => '/1/1/120/',
                    'product_160'  => '/1/1/160/',
                ];
            }

            $this->media = new Model\MediaList();

            if (array_key_exists('id', $data)) $this->id = $data['id'] ? (string)$data['id'] : null;
            if (array_key_exists('token', $data)) $this->token = $data['token'] ? (string)$data['token'] : null;
            if (array_key_exists('name', $data)) $this->name = $data['name'] ? (string)$data['name'] : null;
            if (array_key_exists('link', $data)) $this->link = $data['link'] ? (string)$data['link'] : null;

            // ядерные фотографии
            call_user_func(function() use (&$data, &$photoUrlSizes) {
                // host
                $hosts = $this->getConfig()->mediaHosts;
                $index = !empty($photoId) ? ($photoId % count($hosts)) : rand(0, count($hosts) - 1);
                $host = isset($hosts[$index]) ? $hosts[$index] : '';

                // преобразование в формат scms
                $item = [
                    'content_type' => 'image/jpeg',
                    'provider'     => 'image',
                    'tags'         => ['main'],
                    'sources'      => [],
                ];
                foreach ($photoUrlSizes as $type => $prefix) {
                    $item['sources'][] = [
                        'type' => $type,
                        'url'  => $host . $prefix . $data['image'],
                    ];
                }

                $this->media->photos[] = new Model\Media($item);
            });
        }
    }
}