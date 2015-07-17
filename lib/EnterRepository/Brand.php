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
    
    public function getPopularObjects() {
        $brands = [
            [
                'name' => 'Bosch',
                'sliceId' => 'brands-bosch',
                'medias' => '/img/main/logo/bosch.jpg',
            ],
            [
                'name' => 'LG',
                'sliceId' => 'brands-lg',
                'medias' => '/img/main/logo/lg.jpg',
            ],
            [
                'name' => 'Samsung',
                'sliceId' => 'brands-samsung',
                'medias' => '/img/main/logo/samsung.jpg',
            ],
            [
                'name' => 'Philips',
                'sliceId' => 'brands-philips',
                'medias' => '/img/main/logo/philips.jpg',
            ],
            [
                'name' => 'Electrolux',
                'sliceId' => 'brands-electrolux',
                'medias' => '/img/main/logo/electrolux.jpg',
            ],
            [
                'name' => 'Sony',
                'sliceId' => 'brands-sony',
                'medias' => '/img/main/logo/sony.jpg',
            ],
            [
                'name' => 'Apple',
                'sliceId' => 'brands-apple',
                'medias' => '/img/main/logo/apple.jpg',
            ],
            [
                'name' => 'HP',
                'sliceId' => 'brands-hp',
                'medias' => '/img/main/logo/HP.jpg',
            ],
            [
                'name' => 'Lenovo',
                'sliceId' => 'brands-lenovo',
                'medias' => '/img/main/logo/lenovo.jpg',
            ],
            [
                'name' => 'Hasbro',
                'sliceId' => 'brands-hasbro',
                'medias' => '/img/main/logo/hasHasbrobro.jpg',
            ],
            [
                'name' => 'Sylvanian Families',
                'sliceId' => 'brands-sylvanian-families',
                'medias' => '/img/main/logo/Sylvanian-Families.jpg',
            ],
            [
                'name' => 'LEGO',
                'sliceId' => 'brands-lego',
                'medias' => '/img/main/logo/lego.jpg',
            ],
            [
                'name' => 'Анзоли',
                'sliceId' => 'brands-anzoli',
                'medias' => '/img/main/logo/anzoli.jpg',
            ],
            [
                'name' => 'Шатура',
                'sliceId' => 'brands-shatura',
                'medias' => '/img/main/logo/shatura.jpg',
            ],
            [
                'name' => 'Vision Fitness',
                'sliceId' => 'brands-vision',
                'medias' => '/img/main/logo/visionfitnes.jpg',
            ],
            [
                'name' => 'Makita',
                'sliceId' => 'brands-makita',
                'medias' => '/img/main/logo/Makita.jpg',
            ],
            [
                'name' => 'Аскона',
                'sliceId' => 'brands-askona',
                'medias' => '/img/main/logo/askona.jpg',
            ],
            [
                'name' => 'Tefal',
                'sliceId' => 'brands-tefal',
                'medias' => '/img/main/logo/tefal.jpg',
            ],
            [
                'name' => 'PANDORA',
                'sliceId' => 'brands-pandora',
                'medias' => '/img/main/logo/pandora.jpg',
            ],
            [
                'name' => 'GUESS',
                'sliceId' => 'brands-guess',
                'medias' => '/img/main/logo/guess.jpg',
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
                            'url' => 'http://' . $this->getConfig()->hostname . ($this->getConfig()->version ? '/' . $this->getConfig()->version : '') . $brand['medias'],
                        ],
                    ],
                ],
            ];
            
            $brand = new Model\Brand($brand);
        }
        
        return $brands;
    }
}