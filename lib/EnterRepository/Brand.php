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
                'url' => '/slices/brands-bosch',
                'medias' => 'img/main/logo/bosch.jpg',
            ],
            [
                'name' => 'LG',
                'url' => '/slices/brands-lg',
                'medias' => 'img/main/logo/lg.jpg',
            ],
            [
                'name' => 'Samsung',
                'url' => '/slices/brands-samsung',
                'medias' => 'img/main/logo/samsung.jpg',
            ],
            [
                'name' => 'Philips',
                'url' => '/slices/brands-philips',
                'medias' => 'img/main/logo/philips.jpg',
            ],
            [
                'name' => 'Electrolux',
                'url' => '/slices/brands-electrolux',
                'medias' => 'img/main/logo/electrolux.jpg',
            ],
            [
                'name' => 'Sony',
                'url' => '/slices/brands-sony',
                'medias' => 'img/main/logo/sony.jpg',
            ],
            [
                'name' => 'Apple',
                'url' => '/slices/brands-apple',
                'medias' => 'img/main/logo/apple.jpg',
            ],
            [
                'name' => 'HP',
                'url' => '/slices/brands-hp',
                'medias' => 'img/main/logo/HP.jpg',
            ],
            [
                'name' => 'Lenovo',
                'url' => '/slices/brands-lenovo',
                'medias' => 'img/main/logo/lenovo.jpg',
            ],
            [
                'name' => 'Hasbro',
                'url' => '/slices/brands-hasbro',
                'medias' => 'img/main/logo/hasHasbrobro.jpg',
            ],
            [
                'name' => 'Sylvanian Families',
                'url' => '/slices/brands-sylvanian-families',
                'medias' => 'img/main/logo/Sylvanian-Families.jpg',
            ],
            [
                'name' => 'LEGO',
                'url' => '/slices/brands-lego',
                'medias' => 'img/main/logo/lego.jpg',
            ],
            [
                'name' => 'Анзоли',
                'url' => '/slices/brands-anzoli',
                'medias' => 'img/main/logo/anzoli.jpg',
            ],
            [
                'name' => 'Шатура',
                'url' => '/slices/brands-shatura',
                'medias' => 'img/main/logo/shatura.jpg',
            ],
            [
                'name' => 'Vision Fitness',
                'url' => '/slices/brands-vision',
                'medias' => 'img/main/logo/visionfitnes.jpg',
            ],
            [
                'name' => 'Makita',
                'url' => '/slices/brands-makita',
                'medias' => 'img/main/logo/Makita.jpg',
            ],
            [
                'name' => 'Аскона',
                'url' => '/slices/brands-askona',
                'medias' => 'img/main/logo/askona.jpg',
            ],
            [
                'name' => 'Tefal',
                'url' => '/slices/brands-tefal',
                'medias' => 'img/main/logo/tefal.jpg',
            ],
            [
                'name' => 'PANDORA',
                'url' => '/slices/brands-pandora',
                'medias' => 'img/main/logo/pandora.jpg',
            ],
            [
                'name' => 'GUESS',
                'url' => '/slices/brands-guess',
                'medias' => 'img/main/logo/guess.jpg',
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
                            'url' => 'http://' . $this->getConfig()->hostname . '/' . $brand['medias'],
                        ],
                    ],
                ],
            ];
            
            $brand = new Model\Brand($brand);
        }
        
        return $brands;
    }
}