<?php
namespace EnterModel\Cart\Split;

use EnterMobileApplication\ConfigTrait;

class PaymentMethod {
    use ConfigTrait;

    /** @var string|null */
    public $id;
    /** @var string|null */
    public $ui;
    /** @var string|null */
    public $name;
    /** @var string|null */
    public $description;
    /** @var bool */
    public $isOnline;
    /** @var \EnterModel\MediaList */
    public $media;

    /**
     * @param array $data
     */
    public function __construct($data = []) {
        $this->id = $data['id'] ? (string)$data['id'] : null;
        $this->ui = $data['ui'] ? (string)$data['ui'] : null;
        $this->name = $data['name'] ? (string)$data['name'] : null;
        $this->description = $data['description'] ? (string)$data['description'] : null;
        $this->isOnline = (bool)$data['is_online'];

        $mediaUrlPrefix = 'http://' . $this->getConfig()->hostname . ($this->getConfig()->version ? '/' . $this->getConfig()->version : '');
        $this->media = new \EnterModel\MediaList();

        if ($this->isOnline) {
            switch ($this->id) {
                case 5:
                    $this->media->photos[] = new \EnterModel\Media([
                        'content_type' => 'image/png',
                        'provider' => 'image',
                        'tags' => ['logo', 'card'],
                        'sources' => [
                            [
                                'url' => $mediaUrlPrefix . '/img/payment/logos/original/card.png',
                                'type' => 'original',
                                'width' => '',
                                'height' => '',
                            ],
                        ],
                    ]);
                    break;

                case 16:
                    $this->media->photos[] = new \EnterModel\Media([
                        'content_type' => 'image/png',
                        'provider' => 'image',
                        'tags' => ['logo', 'yandex'],
                        'sources' => [
                            [
                                'url' => $mediaUrlPrefix . '/img/payment/logos/original/yandex.png',
                                'type' => 'original',
                                'width' => '',
                                'height' => '',
                            ],
                        ],
                    ]);
                    break;

                case 11:
                    $this->media->photos[] = new \EnterModel\Media([
                        'content_type' => 'image/png',
                        'provider' => 'image',
                        'tags' => ['logo', 'webmoney'],
                        'sources' => [
                            [
                                'url' => $mediaUrlPrefix . '/img/payment/logos/original/webmoney.png',
                                'type' => 'original',
                                'width' => '',
                                'height' => '',
                            ],
                        ],
                    ]);
                    break;

                case 12:
                    $this->media->photos[] = new \EnterModel\Media([
                        'content_type' => 'image/png',
                        'provider' => 'image',
                        'tags' => ['logo', 'qiwi'],
                        'sources' => [
                            [
                                'url' => $mediaUrlPrefix . '/img/payment/logos/original/qiwi.png',
                                'type' => 'original',
                                'width' => '',
                                'height' => '',
                            ],
                        ],
                    ]);
                    break;
            }
        }
    }

    /**
     * @return array
     */
    public function dump() {
        return [
            'id'          => $this->id,
            'ui'          => $this->ui,
            'name'        => $this->name,
            'description' => $this->description,
            'is_online'   => $this->isOnline,
        ];
    }
}
