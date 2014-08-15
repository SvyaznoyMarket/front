<?php

namespace EnterModel;

use EnterModel as Model;

class PaymentMethod {
    /** @var string */
    public $id;
    /** @var string */
    public $name;
    /** @var string */
    public $description;
    /** @var bool */
    public $isCredit;
    /** @var bool */
    public $isOnline;
    /** @var bool */
    public $isCorporative;
    /** @var string */
    public $groupId;
    /** @var Model\PaymentGroup|null */
    public $group;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
        if (array_key_exists('name', $data)) $this->name = (string)$data['name'];
        if (array_key_exists('description', $data)) $this->description = (string)$data['description'];
        if (array_key_exists('is_credit', $data)) $this->isCredit = (bool)$data['is_credit'];
        if (array_key_exists('is_online', $data)) $this->isOnline = (bool)$data['is_online'];
        if (array_key_exists('is_corporative', $data)) $this->isCorporative = (bool)$data['is_corporative'];
        if (array_key_exists('payment_method_group_id', $data)) $this->groupId = (string)$data['payment_method_group_id'];
    }
}