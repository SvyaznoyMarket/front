<?php

namespace EnterQuery\User\Address;

use Enter\Curl\Query;
use EnterQuery\CrmQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class UpdateItemByUserUi extends Query {
    use CrmQueryTrait;

    /** @var array */
    protected $result;


    public function __construct($userUi, Model\Address $address) {
        $this->url = new Url();
        $this->url->path = 'api/address/update';
        $this->data = [
            'user_uid'    => $userUi,
            'id'          => $address->id,
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