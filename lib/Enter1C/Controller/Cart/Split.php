<?php

namespace Enter1C\Controller\Cart;

use Enter\Http;
use Enter1C\Http\XmlResponse;
use Enter1C\ConfigTrait;
use Enter1C\XmlTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\LoggerTrait;
use EnterQuery as Query;
use EnterModel as Model;
use Enter1C\Repository;

class Split {
    use ConfigTrait, LoggerTrait, CurlTrait, XmlTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return XmlResponse
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();

        $requestData = $this->convertXmlToArray($request->getContent());
        $splitRepository = new Repository\Cart\Split();

        $cart = new Model\Cart();
        foreach ($requestData['cart'] as $productItem) {
            $cart->product[] = new Model\Cart\Product($productItem);
        }

        $splitQuery = new Query\Cart\Split\GetItem(
            $cart,
            new Model\Region(['code' => $requestData['geo_code']]),
            new Model\Shop(['ui' => $requestData['shop_ui']]),
            new Model\PaymentMethod(['ui' => $requestData['payment_method_ui']]),
            isset($requestData['previous_split']) && is_array($requestData['previous_split']) ? $splitRepository->convertXmlArrayToCoreArray($requestData['previous_split']) : [],
            isset($requestData['changes']) && is_array($requestData['changes']) ? $splitRepository->convertXmlArrayToCoreArray($requestData['changes']) : [],
            null,
            false
        );

        $splitQuery->setTimeout($config->coreService->timeout * 2);
        $curl->prepare($splitQuery);
        $curl->execute();

        $split = new Model\Cart\Split($splitQuery->getResult());

        // CAPI-1
        if ((isset($requestData['point']['token']) && isset($requestData['point']['id'])) || isset($requestData['point']['ui'])) {
            $splitQuery = new Query\Cart\Split\GetItem(
                $cart,
                new Model\Region(['code' => $requestData['geo_code']]),
                new Model\Shop(['ui' => $requestData['shop_ui']]),
                new Model\PaymentMethod(['ui' => $requestData['payment_method_ui']]),
                $split->dump(),
                $this->getChangesWithPoint(
                    $split->dump(),
                    isset($requestData['point']['token']) ? $requestData['point']['token'] : null,
                    isset($requestData['point']['id']) ? $requestData['point']['id'] : null,
                    isset($requestData['point']['ui']) ? $requestData['point']['ui'] : null
                ),
                null,
                false
            );

            $splitQuery->setTimeout($config->coreService->timeout * 2);
            $curl->prepare($splitQuery);
            $curl->execute();

            $split = new Model\Cart\Split($splitQuery->getResult());
        }

        return new XmlResponse($splitRepository->convertObjectToXmlArray($split));
    }

    private function getChangesWithPoint(array $splitDump, $pointToken, $pointId, $pointUi) {
        foreach ($splitDump['orders'] as $orderToken => $order) {
            if (isset($pointToken) && isset($pointId)) {
                $splitDump['orders'][$orderToken]['delivery']['point'] = [
                    'token' => $pointToken,
                    'id' => $pointId,
                ];
            } else {
                $splitDump['orders'][$orderToken]['delivery']['point'] = [
                    'ui' => $pointUi,
                ];
            }
        }
        return $splitDump;
    }
}