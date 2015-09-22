<?php

namespace EnterModel\Product {

    use EnterModel as Model;

    class Category {
        /** @var string */
        public $id;
        /** @var string */
        public $ui;
        /** @var string */
        public $name;
        /** @var string */
        public $token;
        /** @var string */
        public $link;
        /** @var string */
        public $path;
        /** @var int */
        public $level;
        /** @var int */
        public $productCount;
        /** @var Model\MediaList */
        public $media;
        /** @var Model\Product\Category\Meta */
        public $meta;
        /** @var bool */
        public $isFurniture;
        /** @var bool */
        public $hasChildren;
        /** @var Model\Product\Category[] */
        public $children = [];
        /** @var Model\Product\Category|null */
        public $parent;

        /**
         * @param mixed $data
         */
        public function __construct($data = []) {
            if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
            if (array_key_exists('core_id', $data)) $this->id = (string)$data['core_id'];
            if (array_key_exists('uid', $data)) $this->ui = (string)$data['uid'];
            if (array_key_exists('name', $data)) $this->name = (string)$data['name'];
            if (array_key_exists('slug', $data)) $this->token = (string)$data['slug'];
            if (array_key_exists('link', $data)) $this->link = rtrim((string)$data['link'], '/'); // Возвращается методом https://scms.enter.ru/api/category/tree
            if (array_key_exists('url', $data)) $this->link = (string)$data['url']; // Возвращается методами https://scms.enter.ru/category/get/v1 и https://scms.enter.ru/product/get-description/v1
            if (array_key_exists('is_furniture', $data)) $this->isFurniture = (bool)$data['is_furniture'];
            $this->path = trim(preg_replace('/^\/catalog\//', '', $this->link), '/');
            $this->media = new Model\MediaList(isset($data['medias']) ? $data['medias'] : []);
            if (array_key_exists('level', $data)) $this->level = (int)$data['level'];
            if (array_key_exists('has_children', $data)) $this->hasChildren = (bool)$data['has_children'];
            if (array_key_exists('product_count', $data)) $this->productCount = (int)$data['product_count'];
            if (isset($data['children']) && is_array($data['children'])) {
                foreach ($data['children'] as $childItem) {
                    if (!isset($childItem['uid'])) continue;
                    $this->children[] = new Model\Product\Category($childItem);
                }
            }

            if (!empty($data['parent'])) {
                $this->parent = new Model\Product\Category($data['parent']);
            }

            $this->meta = new Model\Product\Category\Meta();
            if (isset($data['html_title'])) $this->meta->title = (string)$data['html_title'];
            if (isset($data['meta_keywords'])) $this->meta->keywords = (string)$data['meta_keywords'];
            if (isset($data['meta_description'])) $this->meta->description = (string)$data['meta_description'];
        }

        /**
         * @param array $data
         */
        public function fromArray(array $data) {
            if (isset($data['id'])) $this->id = (string)$data['id'];
            if (isset($data['ui'])) $this->ui = (string)$data['ui'];
            if (isset($data['name'])) $this->name = (string)$data['name'];
            if (isset($data['link'])) $this->link = (string)$data['link'];
            if (isset($data['parent'])) {
                $this->parent = new Model\Product\Category();
                $this->parent->fromArray($data['parent']);
            }
        }
    }
}

namespace EnterModel\Product\Category {
    class Meta {
        /** @var string */
        public $title;
        /** @var string */
        public $keywords;
        /** @var string */
        public $description;
    }
}