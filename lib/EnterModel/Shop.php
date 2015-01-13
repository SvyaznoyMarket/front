<?php

namespace EnterModel;

use EnterModel as Model;

class Shop {
    /** @var string */
    public $id;
    /** @var string */
    public $ui;
    /** @var string */
    public $token;
    /** @var string */
    public $name;
    /** @var string */
    public $regionId;
    /** @var string */
    public $regime;
    /** @var string */
    public $phone;
    /** @var float */
    public $latitude;
    /** @var float */
    public $longitude;
    /** @var string */
    public $address;
    /** @var string */
    public $description;
    /** @var Model\Region|null */
    public $region;
    /** @var Model\Shop\Photo[] */
    public $photo = [];
    /** @var string */
    public $walkWay;
    /** @var string */
    public $carWay;
    /** @var Model\Subway[] */
    public $subway = [];
    /** @var bool */
    public $hasGreenCorridor;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        $this->media = new Model\Shop\Media();

        if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
        if (array_key_exists('ui', $data)) $this->ui = (string)$data['ui'];
        if (array_key_exists('token', $data)) $this->token = (string)$data['token']; // FIXME: deprecated
        if (array_key_exists('slug', $data)) $this->token = (string)$data['slug'];
        if (array_key_exists('name', $data)) $this->name = (string)$data['name'];
        if (array_key_exists('geo_id', $data)) $this->regionId = (string)$data['geo_id'];
        if (array_key_exists('working_time', $data)) $this->regime = (string)$data['working_time']; // FIXME: deprecated
        if (isset($data['working_time']['common'])) $this->regime = (string)$data['working_time']['common'];

        if (array_key_exists('coord_long', $data)) $this->longitude = (float)$data['coord_long']; // FIXME: deprecated
        if (array_key_exists('coord_lat', $data)) $this->latitude = (float)$data['coord_lat']; // FIXME: deprecated
        if (isset($data['location']['longitude'])) $this->longitude = (float)$data['location']['longitude'];
        if (isset($data['location']['latitude'])) $this->latitude = (float)$data['location']['latitude'];

        if (array_key_exists('address', $data)) $this->address = (string)$data['address'];
        if (array_key_exists('phone', $data)) $this->phone = (string)$data['phone'];
        if (array_key_exists('description', $data)) $this->description = (string)$data['description'];
        if (array_key_exists('way_walk', $data)) $this->walkWay = (string)$data['way_walk'];
        if (array_key_exists('way_auto', $data)) $this->carWay = (string)$data['way_auto'];
        if (array_key_exists('is_green_corridor', $data)) $this->hasGreenCorridor = (bool)$data['is_green_corridor']; // FIXME: deprecated
        if (array_key_exists('green_channel', $data)) $this->hasGreenCorridor = (bool)$data['green_channel'];

        if (isset($data['geo']['id'])) {
            $this->region = new Model\Region($data['geo']);
            $this->regionId = $this->region->id; // FIXME: костыль для ядра: иногда не отдает geo_id
        };

        if (isset($data['images'][0])) { // FIXME: deprecated
            foreach ($data['images'] as $photoItem) {
                $this->photo[] = new Model\Shop\Photo($photoItem);
            }
        }
        if (isset($data['medias'][0])) {
            foreach ($data['medias'] as $mediaItem) {
                if (!isset($mediaItem['sources'][0])) continue;

                $media = new Model\Media($mediaItem);

                if ('image' == $media->type) {
                    $this->media->photos[] = new Model\Media($mediaItem);
                }
            }
        }

        if (isset($data['subway'][0]['ui'])) { // FIXME: deprecated
            foreach ($data['subway'] as $subwayItem) {
                $this->subway[] = new Model\Subway($subwayItem);
            }
        };
        if (isset($data['subway']['uid'])) {
            $this->subway[] = new Model\Subway($data['subway']);
        };
    }
}