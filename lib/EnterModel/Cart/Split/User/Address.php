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
        if (isset($data['street'])) {
            $this->street = (string)$data['street'];
        }

        if (isset($data['building'])) {
            $this->building = (string)$data['building'];
        }

        if (isset($data['number'])) {
            $this->number = (string)$data['number'];
        }

        if (isset($data['apartment'])) {
            $this->apartment = (string)$data['apartment'];
        }

        if (isset($data['floor'])) {
            $this->floor = (string)$data['floor'];
        }

        if (isset($data['metro_station'])) {
            $this->subwayName = (string)$data['metro_station'];
        }

        if (isset($data['kladr_id'])) {
            $this->kladrId = (string)$data['kladr_id'];
        }
    }
}
