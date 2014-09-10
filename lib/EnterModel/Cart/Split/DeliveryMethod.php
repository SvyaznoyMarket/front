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
        $this->token = $data['token'] ? (string)$data['token'] : null;
        $this->typeId = $data['type_id'] ? (string)$data['type_id'] : null;
        $this->typeUi = $data['type_ui'] ? (string)$data['type_ui'] : null;
        $this->name = $data['name'] ? (string)$data['name'] : null;
        $this->pointToken = $data['point_token'] ? (string)$data['point_token'] : null;
        $this->groupId = $data['group_id'] ? (string)$data['group_id'] : null;
        $this->description = $data['description'] ? (string)$data['description'] : null;
    }
}
