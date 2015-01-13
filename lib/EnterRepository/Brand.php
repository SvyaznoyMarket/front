<?php

namespace EnterRepository;

use Enter\Http;
use Enter\Curl\Query;
use Enter\Logging\Logger;
use EnterAggregator\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterModel as Model;

class Brand {
    use ConfigTrait, LoggerTrait;

    /** @var Logger */
    protected $logger;

    public function __construct() {
        $this->logger = $this->getLogger();
    }

    /**
     * @param Query $query
     * @return Model\Brand
     */
    public function getObjectByQuery(Query $query) {
        $brand = null;

        if ($item = $query->getResult()) {
            $brand = new Model\Brand($item);
        }

        return $brand;
    }
}