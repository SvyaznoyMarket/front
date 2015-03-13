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
        $config = $this->getConfig();
        $promos = [];

        try {
            foreach ($query->getResult() as $item) {
                $typeId = @$item['type_id'];
                if ($typeId != $config->promo->typeId) continue;

                $promos[] = new Model\Promo($item);
            }
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['repository']]);
            //trigger_error($e, E_USER_ERROR);
        }

        return $promos;
    }

    /**
     * @param $id
     * @param Query $query
     * @return Model\Promo|null
     */
    public function getObjectByIdAndQuery($id, Query $query) {
        $promo = null;

        try {
            foreach ($query->getResult() as $item) {
                if (isset($item['id']) && ($id == $item['id'])) {
                    $promo = new Model\Promo($item);
                    break;
                }
            }
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['repository']]);
            //trigger_error($e, E_USER_ERROR);
        }

        return $promo;
    }
}