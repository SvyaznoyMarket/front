<?php

namespace EnterModel;

use EnterModel as Model;

class Subway {
    /** @var string */
    public $ui;
    /** @var string */
    public $name;
    /** @var Model\Subway\Line|null */
    public $line;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('ui', $data)) $this->ui = (string)$data['ui']; // FIXME: deprecated
        if (array_key_exists('uid', $data)) $this->ui = (string)$data['uid'];
        if (array_key_exists('name', $data)) $this->name = (string)$data['name'];

        if (isset($data['line']['name'])) {
            $this->line = new Model\Subway\Line($data['line']); // FIXME: deprecated
        }
        if (isset($data['line_name'])) {
            $this->line = new Model\Subway\Line([
                'name'  => @$data['line_name'],
                'color' => @$data['line_color'],
            ]);
        }
    }
}