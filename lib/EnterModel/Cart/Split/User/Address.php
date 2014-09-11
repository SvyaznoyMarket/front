<?php
namespace EnterModel\Cart\Split\User;

class Address {
    /** @var string|null */
    public $street;
    /** @var string|null */
    public $building;
    /** @var string|null */
    public $number;
    /** @var string|null */
    public $apartment;
    /** @var string|null */
    public $floor;
    /** @var string|null */
    public $subwayName;
    /** @var string|null */
    public $kladrId;

    public function __construct($data = []) {
        $this->street = $data['street'] ? (string)$data['street'] : null;
        $this->building = $data['building'] ? (string)$data['building'] : null;
        $this->number = $data['number'] ? (string)$data['number'] : null;
        $this->apartment = $data['apartment'] ? (string)$data['apartment'] : null;
        $this->floor = (string)$data['floor'];
        $this->subwayName = (string)$data['metro_station'];
        $this->kladrId = (string)$data['kladr_id'];
    }
}
