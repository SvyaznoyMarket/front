<?php

namespace EnterModel\Product {

    use EnterModel as Model;
    use EnterAggregator\ConfigTrait; // FIXME!!!

    class Category {
        use ConfigTrait;

        /** @var string */
        public $id;
        /** @var string */
        public $ui;
        /** @var string|null */
        public $parentId;
        /** @var string */
        public $name;
        /** @var string */
        public $token;
        /** @var string */
        public $link;
        /** @var string */
        public $path;
        /**
         * @deprecated
         * @var string
         */
        public $image;
        /** @var int */
        public $level;
        /** @var bool */
        public $hasChildren;
        /** @var Model\Product\Category[] */
        public $children = [];
        /** @var int */
        public $productCount;
        /** @var int */
        public $productGlobalCount;
        /** @var Model\Product\Category|null */
        public $parent;
        /** @var Model\Product\Category[] */
        public $ascendants = [];
        /** @var Model\Product\Category\Media */
        public $media;
        /** @var Model\Product\Category\Meta */
        public $meta;
        /** @var bool */
        public $isFurniture;

        /**
         * @param array $data
         */
        public function __construct(array $data = []) {
            //$applicationTags = (array)$this->getConfig()->applicationTags;

            $this->media = new Model\Product\Category\Media();

            if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
            if (array_key_exists('ui', $data)) $this->ui = (string)$data['ui']; // FIXME: deprecated
            if (array_key_exists('uid', $data)) $this->ui = (string)$data['uid'];
            if (array_key_exists('parent_id', $data)) $this->parentId = $data['parent_id'] ? (string)$data['parent_id'] : null;
            if (array_key_exists('name', $data)) $this->name = (string)$data['name'];
            if (array_key_exists('token', $data)) $this->token = (string)$data['token'];
            if (array_key_exists('slug', $data)) $this->token = (string)$data['slug'];
            if (array_key_exists('link', $data)) $this->link = (string)$data['link']; // элемент link возвращает метод https://scms.enter.ru/api/category/tree
            if (array_key_exists('url', $data)) $this->link = (string)$data['url']; // элемент url возвращает метод https://scms.enter.ru/category/get/v1
            if (array_key_exists('is_furniture', $data)) $this->isFurniture = (bool)$data['is_furniture'];

            $this->path = trim(preg_replace('/^\/catalog\//', '', $this->link), '/');
            if (isset($data['medias'][0])) {
                foreach ($data['medias'] as $mediaItem) {
                    if (!isset($mediaItem['sources'][0])) continue;

                    $media = new Model\Media($mediaItem);

                    if ('image' == $media->type) {
                        $this->media->photos[] = new Model\Media($mediaItem);
                    }
                }
            }
            if (array_key_exists('level', $data)) $this->level = (int)$data['level'];
            if (array_key_exists('has_children', $data)) $this->hasChildren = (bool)$data['has_children'];
            if (array_key_exists('product_count', $data)) $this->productCount = (int)$data['product_count'];
            if (array_key_exists('product_count_global', $data
            )) $this->productGlobalCount = (int)$data['product_count_global'];
            if (isset($data['children']) && is_array($data['children'])) {
                foreach ($data['children'] as $childItem) {
                    if (!isset($childItem['uid'])) continue;
                    $this->children[] = new Model\Product\Category($childItem);
                }
            }

            if (isset($data['parent']['uid'])) {
                $this->parent = new Model\Product\Category($data['parent']);
            }

            $this->meta = new Model\Product\Category\Meta();
            if (isset($data['html_title'])) $this->meta->title = (string)$data['html_title'];
            if (isset($data['meta_keywords'])) $this->meta->keywords = (string)$data['meta_keywords'];
            if (isset($data['meta_description'])) $this->meta->description = (string)$data['meta_description'];
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