<?php
namespace EnterModel\Cart\Split;

use EnterModel as Model;
use EnterModel\Cart\Split\PointGroup\Point;

class PointGroup {
    /** @var string */
    public $token;
    /** @var string */
    public $actionName;
    /** @var string */
    public $blockName;
    /** @var Point[] */
    public $points = [];

    public function __construct($data = []) {
        if (isset($data['token'])) {
            $this->token = (string)$data['token'];
        }

        if (isset($data['action_name'])) {
            $this->actionName = (string)$data['action_name'];
        }

        if (isset($data['block_name'])) {
            $this->blockName = (string)$data['block_name'];
        }

        if (isset($data['list']) && is_array($data['list'])) {
            foreach ($data['list'] as $item) {
                $this->points[] = new Point($item);
            }
        }
    }
}
