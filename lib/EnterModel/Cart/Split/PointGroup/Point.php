<?php
namespace EnterModel\Cart\Split\PointGroup;

use EnterModel as Model;
use EnterModel\Subway;
use EnterModel;

class Point {
    /** @var string|null */
    public $id;
    /** @var string|null */
    public $ui;
    /** @var string|null */
    public $number;
    /** @var string|null */
    public $name;
    /** @var string|null */
    public $address;
    /** @var string|null */
    public $house;
    /** @var string|null */
    public $workingTime;
    /** @var float|null */
    public $latitude;
    /** @var float|null */
    public $longitude;
    /** @var Model\Subway[] */
    public $subway = [];

    /**
     * @param array $data
     */
    public function __construct($data = []) {
        if (isset($data['id'])) {
            $this->id = (string)$data['id'];
        }

        if (isset($data['ui'])) {
            $this->ui = (string)$data['ui'];
        }

        if (isset($data['number'])) {
            $this->number = (string)$data['number'];
        }

        if (isset($data['name'])) {
            $this->name = (string)$data['name'];
        }

        if (isset($data['address'])) {
            $this->address = (string)$data['address'];
        }

        if (isset($data['house'])) {
            $this->house = (string)$data['house'];
        }

        if (isset($data['regtime'])) {
            $this->workingTime = (string)$data['regtime'];
        }

        if (isset($data['latitude'])) {
            $this->latitude = (float)$data['latitude'];
        }

        if (isset($data['longitude'])) {
            $this->longitude = (float)$data['longitude'];
        }

        if (isset($data['subway']) && is_array($data['subway'])) {
            foreach ($data['subway'] as $item) {
                $this->subway[] = new Model\Subway($item);
            }
        }
    }

    /**
     * @return array
     */
    public function dump() {
        return [
            'id'        => $this->id,
            'ui'        => $this->ui,
            'number'    => $this->number,
            'name'      => $this->name,
            'address'   => $this->address,
            'house'     => $this->house,
            'regtime'   => $this->workingTime,
            'latitude'  => $this->latitude,
            'longitude' => $this->longitude,
            'subway'    => $this->subway ? array_map(function(Model\Subway $subway) {
                return [
                    'ui'   => $subway->ui,
                    'name' => $subway->name,
                    'line' => $subway->line ? [
                        'name'  => $subway->line->name,
                        'color' => $subway->line->color,
                    ] : null,
                ];
            }, $this->subway) : null,
        ];
    }

}
