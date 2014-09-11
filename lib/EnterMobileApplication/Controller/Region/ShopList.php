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

        // ответ
        $response = [
            'regions' => [],
        ];

        $regionListQuery = new Query\Region\GetMainList();
        $curl->prepare($regionListQuery);

        $shopListQuery = new Query\Shop\GetList();
        $curl->prepare($shopListQuery);

        $curl->execute();

        $firstRegionDataById = [];
        $regionDataById = [];
        foreach ($regionListQuery->getResult() as $regionItem) {
            $regionId = (string)$regionItem['id'];

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
                'id'   => (string)$shopItem['id'],
                'name' => (string)$shopItem['name'],
            ];

            $regionDataById[$regionId]['shops'][] = $shopItem;
        }

        $response['regions'] = array_values(array_filter($regionDataById, function($regionItem) {
            return (bool)$regionItem['shops'];
        }));

        return new Http\JsonResponse($response);
    }
}