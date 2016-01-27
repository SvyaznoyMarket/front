<?php

namespace EnterRepository;

use Enter\Http;
use EnterModel as Model;

class Media {
    /**
     * @param Model\Media[] $mediaList
     * @param string $mediaTag
     * * @param string $sourceType
     * @return Model\Media\Source
     */
    public function getSourceObjectByList(array $mediaList, $mediaTag, $sourceType) {
        foreach ($mediaList as $media) {
            if (in_array($mediaTag, $media->tags, true)) {
                foreach ($media->sources as $source) {
                    if ($source->type === $sourceType) {
                        return $source;
                    }
                }
            }
        }

        return new Model\Media\Source();
    }

    /**
     * * @param string $sourceType
     * @return Model\Media\Source
     */
    public function getSourceObjectByItem(Model\Media $media, $sourceType) {
        foreach ($media->sources as $source) {
            if ($source->type === $sourceType) {
                return $source;
            }
        }

        return new Model\Media\Source();
    }

    /**
     * @param Model\MediaList $mediaList
     * @param array $mediaTypes Доступные значения: 'photos'
     * @param array $mediaTags
     * @param array $sourceTypes
     * @return array
     */
    public function getMediaListResponse(Model\MediaList $mediaList, array $mediaTypes = ['photos'], array $mediaTags = [], array $sourceTypes = []) {
        $result = [];

        if (in_array('photos', $mediaTypes)) {
            $result['photos'] = array_values(array_filter(array_map(function(Model\Media $media) use($mediaTags, $sourceTypes) {
                if (!array_intersect($media->tags, $mediaTags)) {
                    return null;
                }

                $result = [
                    'uid' => $media->uid,
                    'contentType' => $media->contentType,
                    'type' => $media->type,
                    'tags' => $media->tags,
                    'sources' => array_values(array_filter(array_map(function(Model\Media\ImageSource $source) use($sourceTypes) {
                        if (!in_array($source->type, $sourceTypes, true)) {
                            return null;
                        }

                        return [
                            'width' => $source->width,
                            'height' => $source->height,
                            'type' => $source->type,
                            'url' => $source->url,
                        ];
                    }, $media->sources))),
                ];

                if ($result['sources']) {
                    return $result;
                } else {
                    return null;
                }
            }, $mediaList->photos)));
        }

        return $result;
    }

    /**
     * @return Model\MediaList
     */
    public function getMediaListForPaymentMethod($paymentMethodId, $isOnline, \EnterAggregator\Config $config) {
        $mediaList = new \EnterModel\MediaList();
        $mediaUrlPrefix = 'http://' . $config->hostname . ($config->version ? '/' . $config->version : '');

        if ($isOnline) {
            switch ($paymentMethodId) {
                case 5:
                case 17:
                case 20:
                    $mediaList->photos[] = new \EnterModel\Media([
                        'content_type' => 'image/png',
                        'provider' => 'image',
                        'tags' => ['logo', 'card'],
                        'sources' => [
                            [
                                'url' => $mediaUrlPrefix . '/img/payment/logos/original/card.png',
                                'type' => 'original',
                                'width' => '1127',
                                'height' => '200',
                            ],
                        ],
                    ]);
                    break;

                case 16:
                case 21:
                    $mediaList->photos[] = new \EnterModel\Media([
                        'content_type' => 'image/png',
                        'provider' => 'image',
                        'tags' => ['logo', 'yandex'],
                        'sources' => [
                            [
                                'url' => $mediaUrlPrefix . '/img/payment/logos/original/yandex.png',
                                'type' => 'original',
                                'width' => '176',
                                'height' => '200',
                            ],
                        ],
                    ]);
                    break;

                case 11:
                case 19:
                    $mediaList->photos[] = new \EnterModel\Media([
                        'content_type' => 'image/png',
                        'provider' => 'image',
                        'tags' => ['logo', 'webmoney'],
                        'sources' => [
                            [
                                'url' => $mediaUrlPrefix . '/img/payment/logos/original/webmoney.png',
                                'type' => 'original',
                                'width' => '201',
                                'height' => '200',
                            ],
                        ],
                    ]);
                    break;

                case 12:
                case 18:
                    $mediaList->photos[] = new \EnterModel\Media([
                        'content_type' => 'image/png',
                        'provider' => 'image',
                        'tags' => ['logo', 'qiwi'],
                        'sources' => [
                            [
                                'url' => $mediaUrlPrefix . '/img/payment/logos/original/qiwi.png',
                                'type' => 'original',
                                'width' => '191',
                                'height' => '200',
                            ],
                        ],
                    ]);
                    break;
            }
        }

        return $mediaList;
    }
}