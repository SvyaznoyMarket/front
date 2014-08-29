<?php

namespace EnterRepository\Product;

use Enter\Http;
use Enter\Curl\Query;
use EnterAggregator\LoggerTrait;
use EnterModel as Model;

class Line {
    use LoggerTrait;

    /**
     * @param Query $query
     * @return Model\Product\Line|null
     */
    public function getObjectByQuery(Query $query) {
        $line = null;

        try {
            if ($item = $query->getResult()) {
                $line = new Model\Product\Line($item);
            }
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['repository']]);
        }

        return $line;
    }
}