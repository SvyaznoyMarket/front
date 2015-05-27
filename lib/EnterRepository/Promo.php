<?php

namespace EnterRepository;

use Enter\Http;
use Enter\Curl\Query;
use EnterAggregator\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterModel as Model;

class Promo {
    use LoggerTrait, ConfigTrait;

    /**
     * @param Http\Request $request
     * @return string|null
     */
    public function getIdByHttpRequest(Http\Request $request) {
        return is_scalar($request->query['promoId']) ? (string)$request->query['promoId'] : null;
    }

    /**
     * @param Query $query
     * @return Model\Promo[]
     */
    public function getObjectListByQuery(Query $query) {
        $promos = [];

        try {
            foreach ($query->getResult() as $item) {
                $promo = new Model\Promo($item);
                if ($promo->target && $promo->media && $promo->media->photos) {
                    $promos[] = $promo;
                }
            }
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['repository']]);
            //trigger_error($e, E_USER_ERROR);
        }

        return $promos;
    }
}