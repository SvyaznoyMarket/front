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

    /**
     * @param array $data
     */
    public function __construct($data = []) {
        $this->street = isset($data['street']) ? (string)$data['street'] : null;
        $this->building = isset($data['building']) ? (string)$data['building'] : null;
        $this->number = isset($data['number']) ? (string)$data['number'] : null;
        $this->apartment = isset($data['apartment']) ? (string)$data['apartment'] : null;
        $this->floor = isset($data['floor']) ? (string)$data['floor'] : null;
        $this->subwayName = isset($data['metro_station']) ? (string)$data['metro_station'] : null;
        $this->kladrId = isset($data['kladr_id']) ? (string)$data['kladr_id'] : null;
    }

    /**
     * @return array
     */
    public function dump() {
        return [
            'street'        => $this->street,
            'building'      => $this->building,
            'number'        => $this->number,
            'apartment'     => $this->apartment,
            'floor'         => $this->floor,
            'metro_station' => $this->subwayName,
            'kladr_id'      => $this->kladrId,
        ];
    }
}
