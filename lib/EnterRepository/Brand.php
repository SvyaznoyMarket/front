<?php

namespace EnterRepository;

use Enter\Http;
use Enter\Curl\Query;
use Enter\Logging\Logger;
use EnterAggregator\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterModel as Model;

class Brand {
    use ConfigTrait, LoggerTrait;

    /** @var Logger */
    protected $logger;

    public function __construct() {
        $this->logger = $this->getLogger();
    }

    /**
     * @param Query $query
     * @return Model\Brand
     */
    public function getObjectByQuery(Query $query) {
        $brand = null;

        if ($item = $query->getResult()) {
            $brand = new Model\Brand($item);
        }

        return $brand;
    }

    /**
     * @return \EnterModel\Brand[]
     */
    public function getPopularObjects() {
        $brands = [
            [
                'name' => 'Bosch',
                'sliceId' => 'brands-bosch',
                'medias' => 'bosch.jpg',
            ],
            [
                'name' => 'LG',
                'sliceId' => 'brands-lg',
                'medias' => 'lg.jpg',
            ],
            [
                'name' => 'Samsung',
                'sliceId' => 'brands-samsung',
                'medias' => 'samsung.jpg',
            ],
            [
                'name' => 'Philips',
                'sliceId' => 'brands-philips',
                'medias' => 'philips.jpg',
            ],
            [
                'name' => 'Electrolux',
                'sliceId' => 'brands-electrolux',
                'medias' => 'electrolux.jpg',
            ],
            [
                'name' => 'Sony',
                'sliceId' => 'brands-sony',
                'medias' => 'sony.jpg',
            ],
            [
                'name' => 'Apple',
                'sliceId' => 'brands-apple',
                'medias' => 'apple.jpg',
            ],
            [
                'name' => 'HP',
                'sliceId' => 'brands-hp',
                'medias' => 'HP.jpg',
            ],
            [
                'name' => 'Lenovo',
                'sliceId' => 'brands-lenovo',
                'medias' => 'lenovo.jpg',
            ],
            [
                'name' => 'Hasbro',
                'sliceId' => 'brands-hasbro',
                'medias' => 'hasHasbrobro.jpg',
            ],
            [
                'name' => 'Sylvanian Families',
                'sliceId' => 'brands-sylvanian-families',
                'medias' => 'Sylvanian-Families.jpg',
            ],
            [
                'name' => 'LEGO',
                'sliceId' => 'brands-lego',
                'medias' => 'lego.jpg',
            ],
            [
                'name' => 'Анзоли',
                'sliceId' => 'brands-anzoli',
                'medias' => 'anzoli.jpg',
            ],
            [
                'name' => 'Шатура',
                'sliceId' => 'brands-shatura',
                'medias' => 'shatura.jpg',
            ],
            [
                'name' => 'Vision Fitness',
                'sliceId' => 'brands-vision',
                'medias' => 'visionfitnes.jpg',
            ],
            [
                'name' => 'Makita',
                'sliceId' => 'brands-makita',
                'medias' => 'Makita.jpg',
            ],
            [
                'name' => 'Аскона',
                'sliceId' => 'brands-askona',
                'medias' => 'askona.jpg',
            ],
            [
                'name' => 'Tefal',
                'sliceId' => 'brands-tefal',
                'medias' => 'tefal.jpg',
            ],
            [
                'name' => 'PANDORA',
                'sliceId' => 'brands-pandora',
                'medias' => 'pandora.jpg',
            ],
            [
                'name' => 'GUESS',
                'sliceId' => 'brands-guess',
                'medias' => 'guess.jpg',
            ],
        ];
        
        foreach ($brands as &$brand) {
            $brand['medias'] = [
                [
                    'content_type' => 'image/jpeg',
                    'provider' => 'image',
                    'tags' => ['main'],
                    'sources' => [
                        [
                            'type' => '70x35',
                            'width' => '70',
                            'height' => '35',
                            'url' => '//' . $this->getConfig()->hostname . ($this->getConfig()->version ? '/' . $this->getConfig()->version : '') . '/img/brands/logos/70x35/' . $brand['medias'],
                        ],
                        [
                            'type' => '140x70',
                            'width' => '140',
                            'height' => '70',
                            'url' => '//' . $this->getConfig()->hostname . ($this->getConfig()->version ? '/' . $this->getConfig()->version : '') . '/img/brands/logos/140x70/' . $brand['medias'],
                        ],
                    ],
                ],
            ];
            
            $brand = new Model\Brand($brand);
        }
        
        return $brands;
    }
}