<?php

namespace EnterModel\Promo\Target;

use EnterAggregator\ConfigTrait;
use EnterModel as Model;

class Content extends Model\Promo\Target {
    use ConfigTrait;

    /** @var string */
    public $type = 'Content';
    /** @var string|null */
    public $contentId;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        parent::__construct($data);
        if (isset($data['token'])) {
            $this->contentId = (string)$data['token'];
        }
    }
}