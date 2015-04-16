<?php

namespace EnterModel;

use EnterModel as Model;

class AbTest {
    /** @var string */
    public $id;
    /** @var string */
    public $name;
    /** @var string */
    public $token;
    /** @var bool */
    public $isActive;
    /** @var string|null */
    public $startAt;
    /** @var string|null */
    public $endAt;
    /** @var Model\AbTest\Item[] */
    public $items = [];
    /** @var int|null */
    public $gaSlotNumber;
    /** @var int|null */
    public $gaSlotScope;
    /** @var string */
    public $value = 'default';

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('uid', $data)) $this->id = (string)$data['uid'];
        if (array_key_exists('name', $data)) $this->name = (string)$data['name'];
        if (array_key_exists('token', $data)) $this->token = (string)$data['token'];
        if (array_key_exists('active', $data)) $this->isActive = (bool)$data['active'];
        if (array_key_exists('starts_at', $data)) $this->startAt = !empty($data['starts_at']) ? $data['starts_at'] : null;
        if (array_key_exists('expires_at', $data)) $this->endAt = !empty($data['expires_at']) ? $data['expires_at'] : null;
        if (isset($data['cases'][0])) {
            foreach ($data['cases'] as $item) {
                $this->items[] = new Model\AbTest\Item($item);
            }
        }
        if (array_key_exists('ga_slot_number', $data)) $this->gaSlotNumber = !empty($data['ga_slot_number']) ? (int)$data['ga_slot_number'] : null;
        if (array_key_exists('ga_slot_scope', $data)) $this->gaSlotScope = !empty($data['ga_slot_scope']) ? (int)$data['ga_slot_scope'] : null;
    }
}