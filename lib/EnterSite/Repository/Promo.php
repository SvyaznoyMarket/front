<?php

namespace EnterSite\Repository;

use Enter\Http;
use Enter\Curl\Query;
use EnterSite\LoggerTrait;
use EnterSite\Model;

class Promo {
    use LoggerTrait;

    /**
     * @param Http\Request $request
     * @return string|null
     */
    public function getIdByHttpRequest(Http\Request $request) {
        return is_scalar($request->query['promoId']) ? (string)$request->query['promoId'] : null;
    }

    /**
     * @param Query $query
     * @return \EnterModel\Promo[]
     */
    public function getObjectListByQuery(Query $query) {
        $promos = [];

        try {
            foreach ($query->getResult() as $item) {
                $promos[] = new \EnterModel\Promo($item);
            }
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'action' => __METHOD__, 'tag' => ['repository']]);
            //trigger_error($e, E_USER_ERROR);
        }

        return $promos;
    }

    /**
     * @param $id
     * @param Query $query
     * @return \EnterModel\Promo|null
     */
    public function getObjectByIdAndQuery($id, Query $query) {
        $promo = null;

        try {
            foreach ($query->getResult() as $item) {
                if (isset($item['id']) && ($id == $item['id'])) {
                    $promo = new \EnterModel\Promo($item);
                    break;
                }
            }
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'action' => __METHOD__, 'tag' => ['repository']]);
            //trigger_error($e, E_USER_ERROR);
        }

        return $promo;
    }
}