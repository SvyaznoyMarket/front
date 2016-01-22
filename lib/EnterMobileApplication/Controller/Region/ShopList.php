<?php

namespace EnterMobileApplication\Controller\Region;

use Enter\Http;
use EnterAggregator\CurlTrait;
use EnterMobileApplication\ConfigTrait;
use EnterQuery as Query;

class ShopList {
    use ConfigTrait, CurlTrait;

    /**
     * @param Http\Request $request
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $curl = $this->getCurl();

        // ид региона
        $filteredRegionId = is_scalar($request->query['regionId']) ? (string)$request->query['regionId'] : null;

        $regionListQuery = new Query\Region\GetMainList();
        $curl->prepare($regionListQuery)->execute();

        $shopListQuery = new Query\Shop\GetList();
        $curl->prepare($shopListQuery);

        $curl->execute();

        $regionDataById = [];
        foreach ((new \EnterRepository\Region())->getObjectListByQuery($regionListQuery) as $region) {
            if (!$region->id || ($filteredRegionId && ($region->id !== $filteredRegionId))) {
                continue;
            }

            $regionDataById[$region->id] = [
                'id'      => $region->id,
                'kladrId' => $region->kladrId,
                'name'    => $region->name,
                'shops'   => [],
            ];
        }

        foreach ($shopListQuery->getResult() as $shopItem) {
            $regionId = !empty($shopItem['geo']['id']) ? (string)$shopItem['geo']['id'] : null;
            if (!$regionId || ($filteredRegionId && ($regionId !== $filteredRegionId))) {
                continue;
            }

            if (!isset($regionDataById[$regionId])) {
                $regionDataById[$regionId] = [
                    'id'      => $regionId,
                    'kladrId' => @$shopItem['geo']['kladr_id'] ? (string)$shopItem['geo']['kladr_id'] : null,
                    'name'    => @$shopItem['geo']['name'] ? (string)$shopItem['geo']['name'] : null,
                    'shops'   => [],
                ];
            }

            $shop = new \EnterModel\Shop($shopItem);

            $regionDataById[$regionId]['shops'][] = [
                'id'               => $shop->id,
                'address'          => $shop->address,
                'longitude'        => $shop->longitude,
                'latitude'         => $shop->latitude,
                'regime'           => $shop->regime,
                'type'             => 'shop',
                'subway'           => $shop->subway,
                'hasGreenCorridor' => $shop->hasGreenCorridor,
            ];
        }

        $firstRegionIds = ['14974', '108136'];
        $firstRegions = [];
        foreach ($firstRegionIds as $firstRegionId) {
            if (isset($regionDataById[$firstRegionId])) {
                $firstRegions[$firstRegionId] = $regionDataById[$firstRegionId];
                unset($regionDataById[$firstRegionId]);
            }
        }

        $regionDataById = array_merge($firstRegions, $regionDataById);

        return new Http\JsonResponse([
            'regions' => array_values(array_map(function($regionData) {
                return [
                    'id'      => (string)$regionData['id'],
                    'kladrId' => $regionData['kladrId'] ? (string)$regionData['kladrId'] : null,
                    'name'    => $regionData['name'] ? (string)$regionData['name'] : null,
                    'shops'   => $regionData['shops'],
                ];
            }, $regionDataById)),
        ]);
    }
}