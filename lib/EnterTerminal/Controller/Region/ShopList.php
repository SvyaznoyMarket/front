<?php

namespace EnterTerminal\Controller\Region;

use Enter\Http;
use EnterAggregator\CurlTrait;
use EnterTerminal\ConfigTrait;
use EnterQuery as Query;
use EnterModel as Model;

class ShopList {
    use ConfigTrait, CurlTrait;

    /**
     * @param Http\Request $request
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $curl = $this->getCurl();

        // ответ
        $response = [
            'regions' => [],
        ];

        // ид региона
        $filteredRegionId = is_scalar($request->query['regionId']) ? (string)$request->query['regionId'] : null;

        $regionListQuery = new Query\Region\GetMainList();
        $curl->prepare($regionListQuery);

        $shopListQuery = new Query\Shop\GetList();
        $curl->prepare($shopListQuery);

        $curl->execute();

        $firstRegionDataById = [];
        $regionDataById = [];
        foreach ($regionListQuery->getResult() as $regionItem) {
            $regionId = (string)$regionItem['id'];

            if ($filteredRegionId && ($regionId !== $filteredRegionId)) continue;

            $regionItem = [
                'id'    => $regionId,
                'name'  => (string)$regionItem['name'],
                'shops' => [],
            ];

            if (in_array($regionId, ['14974', '108136'])) {
                $firstRegionDataById[$regionId] = $regionItem;
            } else {
                $regionDataById[$regionId] = $regionItem;
            }
        }

        $regionDataById = $firstRegionDataById + $regionDataById;

        foreach ($shopListQuery->getResult() as $shopItem) {
            $regionId = !empty($shopItem['geo']['id']) ? (string)$shopItem['geo']['id'] : null;
            if (!$regionId || !isset($regionDataById[$regionId])) continue;

            $shopItem = [
                'id'        => (string)@$shopItem['id'],
                'address'   => (string)@$shopItem['address'],
                'longitude' => (float)@$shopItem['coord_long'] ?: null,
                'latitude'  => (float)@$shopItem['coord_lat'] ?: null,
                'regime'    => (string)@$shopItem['working_time'],
                'type'      => 'shop',
                'subway'    => isset($shopItem['subway'][0]) ? array_map(function($item) {
                    return ['name' => @$item['name'], 'line' => @$item['line']];
                }, $shopItem['subway']) : [],
            ];

            $regionDataById[$regionId]['shops'][] = $shopItem;
        }

        $response['regions'] = array_values(array_filter($regionDataById, function($regionItem) {
            return (bool)$regionItem['shops'];
        }));

        return new Http\JsonResponse($response);
    }
}