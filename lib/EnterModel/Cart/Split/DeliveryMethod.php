<?php
namespace EnterModel\Cart\Split;

class DeliveryMethod {
    /** @var string|null */
    public $token;
    /** @var string|null */
    public $typeId;
    /** @var string|null */
    public $typeUi;
    /** @var string|null */
    public $name;
    /** @var string|null */
    public $pointToken;
    /** @var string|null */
    public $groupId;
    /** @var string|null */
    public $description;

    public function __construct($data = []) {
        if (isset($data['token'])) {
            $this->token = (string)$data['token'];
        }

        if (isset($data['type_id'])) {
            $this->typeId = (string)$data['type_id'];
        }

        if (isset($data['type_ui'])) {
            $this->typeUi = (string)$data['type_ui'];
        }

        if (isset($data['name'])) {
            $this->name = (string)$data['name'];
        }

        if (isset($data['point_token'])) {
            $this->pointToken = (string)$data['point_token'];
        }

        if (isset($data['group_id'])) {
            $this->groupId = (string)$data['group_id'];
        }

        if (isset($data['description'])) {
            $this->description = (string)$data['description'];
        }
    }
}
