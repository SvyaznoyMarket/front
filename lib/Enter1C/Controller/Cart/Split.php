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
            new Model\Region(['ui' => $requestData['geo_ui']]),
            new Model\Shop(['ui' => $requestData['shop_ui']]),
            new Model\PaymentMethod(['ui' => $requestData['payment_method_ui']]),
            isset($requestData['previous_split']) && is_array($requestData['previous_split']) ? $splitRepository->convertXmlArrayToCoreArray($requestData['previous_split']) : [],
            isset($requestData['changes']) && is_array($requestData['changes']) ? $splitRepository->convertXmlArrayToCoreArray($requestData['changes']) : []
        );

        $splitQuery->setTimeout($config->coreService->timeout * 2);
        $curl->prepare($splitQuery);
        $curl->execute();

        $split = new Model\Cart\Split($splitQuery->getResult());
        return new XmlResponse($splitRepository->convertObjectToXmlArray($split));
    }
}