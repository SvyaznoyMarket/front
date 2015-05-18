<?php

namespace EnterQuery\User\Address;

use Enter\Curl\Query;
use EnterQuery\CrmQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class CreateItemByUserUi extends Query {
    use CrmQueryTrait;

    /** @var array */
    protected $result;


    public function __construct($userUi, Model\Address $address) {
        $this->url = new Url();
        $this->url->path = 'api/address/create';
        $this->data = [
            'user_uid'    => $userUi,
            'type'        => $address->type,
            'kladr_id'    => $address->kladrId,
            'geo_id'      => $address->regionId,
            'zip_code'    => $address->zipCode,
            'street'      => $address->street,
            'building'    => $address->building,
            'apartment'   => $address->apartment,
            'description' => $address->description,
        ];

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = $data;
    }
}