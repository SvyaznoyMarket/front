<?php

namespace EnterRepository\Product\Catalog;

use Enter\Curl\Query;
use Enter\Http;
use EnterAggregator\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterModel as Model;

class Config {
    use ConfigTrait, LoggerTrait;

    /**
     * @param Query $query
     * @return Model\Product\Catalog\Config|null
     */
    public function getObjectByQuery(Query $query) {
        $object = null;

        try {
            $item = $query->getResult();
            if ($item) {
                $object = new Model\Product\Catalog\Config($item);
            }
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['repository']]);
            //trigger_error($e, E_USER_ERROR);
        }

        return $object;
    }

    public function getLimitByHttpRequest(Http\Request $request) {
        $limit = (int)$request->query['limit'];
        if (($limit >= 400) || ($limit <= 0)) {
            $limit = $this->getConfig()->product->itemPerPage;
        }

        return $limit;
    }
}