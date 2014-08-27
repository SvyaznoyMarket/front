<?php
namespace EnterModel\Cart\Split\PointGroup;

use EnterModel as Model;
use EnterModel\Subway;
use EnterModel;

class Point {
    /** @var string|null */
    public $id = [];
    /** @var string|null */
    public $ui = [];
    /** @var string|null */
    public $number = [];
    /** @var string|null */
    public $name = [];
    /** @var string|null */
    public $address = [];
    /** @var string|null */
    public $house = [];
    /** @var string|null */
    public $regTime = [];
    /** @var string|null */
    public $latitude = [];
    /** @var string|null */
    public $longitude = [];
    /** @var Model\Subway[] */
    public $subway = [];

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
            $this->regTime = (string)$data['regtime'];
        }

        if (isset($data['latitude'])) {
            $this->latitude = (string)$data['latitude'];
        }

        if (isset($data['longitude'])) {
            $this->longitude = (string)$data['longitude'];
        }

        if (isset($data['subway']) && is_array($data['subway'])) {
            foreach ($data['subway'] as $item) {
                $this->subway[] = new Model\Subway($item);
            }
        }
    }
}
