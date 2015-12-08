<?php

namespace EnterRepository;

use Enter\Http;
use Enter\Curl\Query;
use EnterAggregator\ConfigTrait;
use EnterModel as Model;

class Region {
    use ConfigTrait;

    /**
     * @param Http\Request $request
     * @return string
     */
    public function getIdByHttpRequestCookie(Http\Request $request) {
        $config = $this->getConfig()->region;

        $id = (string)(int)$request->cookies[$config->cookieName] ?: $config->defaultId;
        if ('2041' === $id) {
            $id = $config->defaultId;
        }

        return $id;
    }

    /**
     * @param Http\Request $request
     * @return string
     */
    public function getIdByHttpRequestQuery(Http\Request $request) {
        $id = trim((string)$request->query['regionId']);

        return $id;
    }

    /**
     * @param Query $query
     * @return Model\Region
     */
    public function getObjectByQuery(Query $query) {
        $region = null;

        try {
            $item = $query->getResult();
            if (!$item) {
                // TODO: logger
                $region = new Model\Region(['id' => $this->getConfig()->region->defaultId, 'name' => 'Москва*']);
            } else {
                $region = new Model\Region($item);
            }
        } catch (\Exception $e) {
            $region = new Model\Region(['id' => $this->getConfig()->region->defaultId, 'name' => 'Москва*']);
        }

        return $region;
    }

    /**
     * @param Query $query
     * @return Model\Region[]
     */
    public function getObjectListByQuery(Query $query) {
        $regions = [];

        $firstRegions = [];

        foreach ($query->getResult() as $item) {
            if (empty($item['id'])) continue;

            $region = new Model\Region($item);
            if (in_array($region->id, ['14974', '108136'])) {
                $firstRegions[] = $region;
            } else {
                $regions[] = $region;
            }
        }

        return array_merge($firstRegions, $regions);
    }

    /**
     * @param Query $query
     * @return Model\Region[]
     */
    public function getIndexedByIdObjectListByQuery(Query $query) {
        $regions = [];

        foreach ($query->getResult() as $item) {
            if (empty($item['id'])) continue;

            $region = new Model\Region($item);
            $regions[$region->id] = $region;
        }

        return $regions;
    }
}