<?php

namespace EnterModel {
    use EnterModel as Model;

    class SecretSalePromo {
        /** @var string */
        public $ui = '';
        /** @var string */
        public $name = '';
        /** @var int */
        public $discount = 0;
        /** @var int|null */
        public $startAt;
        /** @var int|null */
        public $endAt;
        /** @var Model\MediaList */
        public $media;
        /** @var Model\Product[] */
        public $products = [];

        /**
         * @param array $data
         */
        public function __construct(array $data = []) {
            if (isset($data['uid'])) $this->ui = (string)$data['uid'];
            if (isset($data['name'])) $this->name = (string)$data['name'];
            if (isset($data['discount'])) $this->discount = (int)$data['discount'];

            try {
                if (!empty($data['starts_at'])) $this->startAt = (int)strtotime((string)$data['starts_at']);
            } catch (\Exception $e) {}

            try {
                if (!empty($data['expires_at'])) $this->endAt = (int)strtotime((string)$data['expires_at']);
            } catch (\Exception $e) {}

            $this->media = new Model\MediaList(isset($data['medias']) ? $data['medias'] : []);

            if (isset($data['products']) && is_array($data['products'])) {
                $this->products = array_map(function($product) {
                    return new Product($product);
                }, $data['products']);
            }
        }
    }
}