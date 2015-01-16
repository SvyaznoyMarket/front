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

        // ид региона
        $filteredRegionId = is_scalar($request->query['regionId']) ? (string)$request->query['regionId'] : null;

        $firstRegionIds = ['14974', '108136'];

        $shopListQuery = new Query\Shop\GetList();
        $curl->prepare($shopListQuery);

        $curl->execute();

        $regionDataById = [];

        foreach ($shopListQuery->getResult() as $shopItem) {
            $regionId = !empty($shopItem['geo']['id']) ? (string)$shopItem['geo']['id'] : null;
            if (
                !$regionId
                || ($filteredRegionId && ($regionId !== $filteredRegionId))
            ) {
                continue;
            }

            if (!isset($regionDataById[$regionId])) {
                $regionDataById[$regionId] = [
                    'id'    => $regionId,
                    'name'  => @$shopItem['geo']['name'] ? (string)$shopItem['geo']['name'] : null,
                    'shops' => [],
                ];
            }

            $shop = new \EnterModel\Shop($shopItem);

            $shopItem = [
                'id'               => $shop->id,
                'address'          => $shop->address,
                'longitude'        => $shop->longitude,
                'latitude'         => $shop->latitude,
                'regime'           => $shop->regime,
                'type'             => 'shop',
                'subway'           => $shop->subway,
                'hasGreenCorridor' => $shop->hasGreenCorridor,
            ];

            $regionDataById[$regionId]['shops'][] = $shopItem;
        }

        try {
            usort($regionDataById, function($a, $b) use (&$firstRegionIds) {
                if (in_array($a['id'], $firstRegionIds)) {
                    return -1;
                } else if (in_array($b['id'], $firstRegionIds)) {
                    return 1;
                } else if ($a['name'] == $b['name']) {
                    return 0;
                }

                return $a['name'] < $b['name'] ? -1 : 1;
            });
        } catch (\Exception $e) {}

        $response['regions'] = array_values(array_filter($regionDataById, function($regionItem) {
            return (bool)$regionItem['shops'];
        }));

        return new Http\JsonResponse($response);
    }
}