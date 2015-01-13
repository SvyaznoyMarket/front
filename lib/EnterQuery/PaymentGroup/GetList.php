<?php

namespace EnterQuery\PaymentGroup;

use Enter\Curl\Query;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetList extends Query {
    use CoreQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param string $regionId
     * @param Model\Cart|null $cart
     * @param array $criteria
     */
    public function __construct($regionId, Model\Cart $cart = null, array $criteria = []) {
        $criteria = array_merge([
            'isCredit'      => false,
            'isOnline'      => false,
            'isPersonal'    => false,
            'isLegal'       => false,
            'isCorporative' => false,
        ], $criteria);

        $this->url = new Url();
        $this->url->path = 'v2/payment-method/get-group';
        $this->url->query = [
            'geo_id'         => $regionId,
        ];

        if ($criteria['isCredit']) $this->url->query['is_credit'] = true;
        if ($criteria['isOnline']) $this->url->query['is_online'] = true;
        if ($criteria['isPersonal']) $this->url->query['is_personal'] = true;
        if ($criteria['isLegal']) $this->url->query['is_legal'] = true;
        if ($criteria['isCorporative']) $this->url->query['is_corporative'] = true;

        if ($cart) {
            $this->data = [
                'product_list'  => array_values(array_map(function(Model\Cart\Product $cartProduct) {
                    return [
                        'id'       => $cartProduct->id,
                        'quantity' => $cartProduct->quantity,
                    ];
                }, $cart->product)),
            ];
        }

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['detail'][0]['id']) ? $data['detail'] : [];
    }
}