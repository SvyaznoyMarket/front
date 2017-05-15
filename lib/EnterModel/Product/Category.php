<?php

namespace EnterModel\Product {

    use EnterModel as Model;

    class Category {
        const UI_MEBEL                                       = 'f7a2f781-c776-4342-81e8-ab2ebe24c51a';
        const UI_SDELAY_SAM                                  = '0e80c81b-31c9-4519-bd10-e6a556fe000c';
        const UI_DETSKIE_TOVARY                              = 'feccd951-d555-42c2-b417-a161a78faf03';
        const UI_DETSKIE_TOVARY_AKSESSUARY_DLYA_AVTOKRESEL   = 'f30feba1-915e-40e5-9344-35b535085a76';
        const UI_TOVARY_DLYA_DOMA                            = 'b8569e65-e31e-47a1-af20-5b06aff9f189';
        const UI_TOVARY_DLYA_DOMA_AKSESSUARY_DLYA_VANNOI     = 'ed1ac096-66b8-4b55-9941-34ade3dc6725';
        const UI_ELECTRONIKA                                 = 'd91b814f-0470-4fd5-a2d0-a0449e63ab6f';
        const UI_PODARKI_I_HOBBY                             = 'c9c2dc8d-1ee5-4355-a0c1-898f219eb892';
        const UI_BYTOVAYA_TEHNIKA                            = '616e6afd-fd4d-4ff4-9fe1-8f78236d9be6';
        const UI_BYTOVAYA_TEHNIKA_AKSESSUARY                 = 'a72e6335-d62c-4a46-85a6-306cd1c8af14';
        const UI_UKRASHENIYA_I_CHASY                         = '022fa1e3-c51f-4a48-87fc-de2c917176d6';
        const UI_PARFUMERIA_I_COSMETIKA                      = '19b9f12c-d489-4540-9a17-23dba0641166';
        const UI_SPORT_I_OTDYH                               = '846eccd2-e9f0-4ce4-b7a2-bb28a835fd7a';
        const UI_SPORT_I_OTDYH_VELOSIPEDY_AKSESSUARY         = '1f087575-d6c2-45f4-8c1b-dadabca45141';
        const UI_ZOOTOVARY                                   = 'b933de12-5037-46db-95a4-370779bb4ee2';
        const UI_ZOOTOVARY_AKSESSUARY_DLYA_AKVARIUMOV        = 'eba838c7-77f0-4e75-a631-b8280caddfc2';
        const UI_TCHIBO                                      = 'caf18e17-550a-4d3e-8285-b1c9cc99b5f4';
        const UI_KRASOTA_I_ZDOROVIE                          = '5f3aa3be-1ac2-4dff-a473-c603e6e51e41';
        const UI_ELECTRONIKA_AKSESSUARY                      = '5e78849d-01e8-4509-8bfe-85f8e148b37d';
        const UI_IGRY_I_KONSOLI                              = 'ed807fca-962b-4b75-9813-d5efbb8ef586';
        const UI_AVTO                                        = 'f0d53c46-d4fc-413f-b5b3-a2b57b93a717';
        const UI_ODEZHDA                                     = 'df56d956-3b07-4fca-ad47-d116a0f5104e';
        const UI_ODEZHDA_AKSESSUARY                          = '6270ed26-3582-4749-8e0d-2e8373f600b0';
        const UI_SAD_I_OGOROD                                = 'e86ccf17-e161-4d5e-8158-2a8ee458b8e7';

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

            // not good fix
            usort($this->media->photos, function (Model\Media $a, Model\Media $b) {
                return count($a->sources) < count($b->sources);
            });

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