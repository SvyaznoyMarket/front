<?php
namespace EnterModel\Cart\Split;

use EnterModel as Model;
use EnterModel\Cart\Split\PointGroup\Point;

class PointGroup {
    /** @var string */
    public $id;
    /** @var string */
    public $token;
    /** @var string */
    public $actionName;
    /** @var string */
    public $blockName;
    /** @var Model\MediaList */
    public $media;
    /** @var Point[] */
    public $points = [];

    /**
     * @param bool $format
     * @param array $data
     */
    public function __construct($data = [], $format = true) {
        $this->token = $data['token'] ? (string)$data['token'] : null;
        $this->actionName = $data['action_name'] ? (string)$data['action_name'] : null;
        $this->blockName = $data['block_name'] ? (string)$data['block_name'] : null;
        foreach ($data['list'] as $key => $item) {
            if ($format) {
                $this->points[] = new Point($item);
            } else {
                $this->points[$key] = new Point($item);
            }
        }
    }

    /**
     * @return array
     */
    public function dump() {
        return [
            'token'       => $this->token,
            'action_name' => $this->actionName,
            'block_name'  => $this->blockName,
            'list'        => array_map(function(Model\Cart\Split\PointGroup\Point $point) { return $point->dump(); }, $this->points),
        ];
    }
}
