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
    /** @var Model\MediaList */
    public $media;
    /** @var Point[] */
    public $points = [];
    /** @var bool */
    private $index;

    /**
     * @param bool $index
     * @param array $data
     */
    public function __construct($data = [], $index = false) {
        $this->index = $index;

        $this->token = $data['token'] ? (string)$data['token'] : null;
        $this->actionName = $data['action_name'] ? (string)$data['action_name'] : null;
        $this->blockName = $data['block_name'] ? (string)$data['block_name'] : null;
        foreach ($data['list'] as $key => $item) {
            if ($this->index) {
                $this->points[$key] = new Point($item);
            } else {
                $this->points[] = new Point($item);
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
