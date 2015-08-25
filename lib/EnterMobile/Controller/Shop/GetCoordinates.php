<?php

namespace EnterMobile\Controller\Shop;

use Enter\Http;
use EnterAggregator\SessionTrait;
use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterAggregator\DebugContainerTrait;
use EnterMobile\Repository;
use EnterQuery as Query;
use EnterMobile\Model;
use EnterMobile\Model\Page\Shops\Index as Page;

class GetCoordinates {

    use ConfigTrait,
        LoggerTrait,
        CurlTrait,
        MustacheRendererTrait,
        DebugContainerTrait,
        SessionTrait;

    public function execute(Http\Request $request) {
        $curl = $this->getCurl();
        $renderer = $this->getRenderer();

        $postData = $request->data->all();
        $searchPhrase = (isset($postData['phrase'])) ? $postData['phrase'] : false;

        if (!$searchPhrase) {
            return new Http\JsonResponse([
                'data' => false
            ]);
        }

        $coordinates = [];
//        try {
            $yq = new Query\Yandex\GetCoordinatesByPhrase($searchPhrase);
            $curl->prepare($yq);
            $curl->execute($yq);
//            $coordinates = $yq->getResult();
//
//        } catch (\Exception $e) {
//            print_r($e);
//        }

        return new Http\JsonResponse([
            'data' => $coordinates
        ]);
    }
}