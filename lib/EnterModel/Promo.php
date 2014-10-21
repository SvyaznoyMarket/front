<?php

namespace EnterModel {
    use EnterModel as Model;

    class Promo {
        /** @var int */
        public $id;
        /** @var int */
        public $type;
        /** @var string */
        public $name;
        /** @var string */
        public $url;
        /** @var string */
        public $image;
        /** @var Model\Promo\Item[] */
        public $items = [];

        /**
         * @param array $data
         */
        public function __construct(array $data = []) {
            if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
            if (array_key_exists('name', $data)) $this->name = (string)$data['name'];
            if (array_key_exists('media_image', $data)) $this->image = (string)$data['media_image'];
            if (array_key_exists('url', $data)) $this->url = $data['url'] ? (string)$data['url'] : null;
            if (isset($data['item_list'][0])) {
                foreach ($data['item_list'] as $item) {
                    $this->items[] = new Model\Promo\Item($item);
                }
            }

            if (!count($this->items)) {
                $contentRepository = new \EnterRepository\Content();

                $item = new Model\Promo\Item();
                $item->typeId = Model\Promo\Item::TYPE_CONTENT;
                $item->contentToken = $contentRepository->getTokenByPath(parse_url($this->url, PHP_URL_PATH));
                $this->items[] = $item;
            }

            if (isset($this->items[0]->productCategoryId)) {
                $this->type = 'ProductCatalog/Category';
            }
            else if (isset($this->items[0]->productId)) {
                if (count($this->items) > 1) {
                    $this->type = 'ProductSet';
                }
                else {
                    $this->type = 'ProductCard';
                }
            }
            else if (isset($this->items[0]->contentToken)) {
                $this->type = 'Content';

                // FIXME
                try {
                    if (preg_match('/[a-z0-9._-]+\/slices\/(\w+)/', $this->url, $matches)) {
                        if (!empty($matches[1])) {
                            $item = new Model\Promo\Item();
                            $item->sliceId = (string)$matches[1];

                            $this->type = 'ProductCatalog/Slice';
                            $this->items = [$item];
                        }
                    }
                } catch (\Exception $e) {}
            }
        }
    }
}



namespace EnterModel\Promo {

    class Item {
        const TYPE_PRODUCT = 1;
        //const TYPE_SERVICE = 2;
        const TYPE_PRODUCT_CATEGORY = 3;
        const TYPE_CONTENT = 4;

        /** @var int */
        public $typeId;
        /** @var string */
        public $productId;
        /** @var string */
        public $sliceId;
        /** @var string */
        public $productCategoryId;
        /** @var string */
        public $contentToken;

        /**
         * @param array $data
         */
        public function __construct(array $data = []) {
            if (array_key_exists('type_id', $data)) $this->typeId = (int)$data['type_id'];
            if (array_key_exists('id', $data)) {
                switch ($this->typeId) {
                    case self::TYPE_PRODUCT:
                        $this->productId = (string)$data['id'];
                        break;
                    /*
                    case self::TYPE_SERVICE:
                        $this->serviceId = (string)$data['id'];
                        break;
                    */
                    case self::TYPE_PRODUCT_CATEGORY:
                        $this->productCategoryId = (string)$data['id'];
                        break;
                }
            }
        }
    }
}

