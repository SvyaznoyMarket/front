<?php

namespace EnterModel\Product;

use EnterModel as Model;

class Property {
    /** @var string */
    public $id;
    /** @var string */
    public $name;
    /** @var string */
    public $unit;
    /** @var string */
    public $hint;
    /** @var string */
    public $valueHint;
    /** @var bool */
    public $isMultiple;
    /** @var string */
    public $value;
    /** @var string */
    public $groupId;
    /** @var int */
    public $groupPosition;
    /** @var int */
    public $position;
    /** @var bool */
    public $isInList;
    /** @var string */
    public $shownValue;
    /** @var Property\Option[] */
    public $options = [];

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('id', $data)) $this->id = (string)$data['id'];
        if (array_key_exists('name', $data)) $this->name = (string)$data['name'];
        if (array_key_exists('unit', $data)) $this->unit = $data['unit'] ? (string)$data['unit'] : null;
        if (array_key_exists('hint', $data)) $this->hint = $data['hint'] ? (string)$data['hint'] : null;
        if (array_key_exists('value_hint', $data)) $this->valueHint = (string)$data['value_hint'];
        if (array_key_exists('is_multiple', $data)) $this->isMultiple = (bool)$data['is_multiple'];
        if (array_key_exists('value', $data)) $this->value = $data['value'];
        if (array_key_exists('group_uid', $data)) {
            $this->groupId = $data['group_uid'] ? (string)$data['group_uid'] : null;
        }
        if (array_key_exists('position_in_group', $data)) {
            $this->groupPosition = (int)$data['position_in_group'];
        }
        if (array_key_exists('position_in_list', $data)) {
            $this->position = (int)$data['position_in_list'];
        }
        if (array_key_exists('is_view_list', $data)) $this->isInList = (bool)$data['is_view_list'];
        if (array_key_exists('options', $data) && is_array($data['options'])) {
            foreach ($data['options'] as $optionItem) {
                if (!isset($optionItem['value'])) continue;

                $this->options[] = new Property\Option($optionItem);
            }
        }

        $this->shownValue = implode(
            ', ',
            array_map(
                function(Model\Product\Property\Option $option) {
                    return $option->value;
                },
                $this->options
            )
        );

        if ($this->shownValue && $this->unit) {
            $this->shownValue .= (' ' . $this->unit);
        }
    }
}