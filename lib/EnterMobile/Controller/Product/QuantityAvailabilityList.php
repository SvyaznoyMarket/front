<?php

namespace EnterMobile\Controller\Product;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\CurlTrait;
use EnterMobile\Controller;
use EnterMobile\Repository;
use EnterQuery as Query;
use EnterMobile\Model;

class QuantityAvailabilityList {
    use ConfigTrait, LoggerTrait, CurlTrait;

    /**
     * @param Http\Request $request
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();
        $cartRepository = new \EnterRepository\Cart();

        // ид региона
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        // запрос региона
        $regionQuery = new Query\Region\GetItemById($regionId);
        $curl->prepare($regionQuery);

        $curl->execute();

        // регион
        $region = (new \EnterRepository\Region())->getObjectByQuery($regionQuery);

        // товары из http-запроса
        $cartProducts = $cartRepository->getProductObjectListByHttpRequest($request);

        $deliveryListQuery = new Query\Product\Delivery\GetListByCartProductList($cartProducts, $region->id);

        $curl->prepare($deliveryListQuery);

        $curl->execute();

        $result = false;
        try {
            $deliveryListQuery->getResult();
            $result = true;
        } catch (\Exception $e) {

        }

        // http-ответ
        $response = new Http\JsonResponse([
            'success' => $result,
        ]);

        return $response;
    }
}