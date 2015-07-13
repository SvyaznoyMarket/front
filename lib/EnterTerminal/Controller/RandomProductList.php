<?php

namespace EnterTerminal\Controller;

use Enter\Http;
use EnterTerminal\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterQuery as Query;
use EnterModel as Model;
use EnterTerminal\Controller;

class RandomProductList {
    use ConfigTrait, CurlTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();
        $productRepository = new \EnterRepository\Product();

        // ид региона
        $regionId = (new \EnterTerminal\Repository\Region())->getIdByHttpRequest($request);
        if (!$regionId) {
            throw new \Exception('Не передан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
        }

        // ид региона
        $shopId = (new \EnterTerminal\Repository\Shop())->getIdByHttpRequest($request);
        if (!$shopId) {
            throw new \Exception('Не передан параметр shopId', Http\Response::STATUS_BAD_REQUEST);
        }

        // запрос региона
        $regionQuery = new Query\Region\GetItemById($regionId);
        $curl->prepare($regionQuery);

        $productUiListQuery = new Query\Product\GetRandomUiList([
            'discount' => 40,
            'shopId'   => $shopId,
            'price'    => 1500,
            'limit'    => 20,
        ]);
        $curl->prepare($productUiListQuery);

        $curl->execute();

        // список айдишников
        $productUis = $productUiListQuery->getResult();

        // регион
        $region = (new \EnterRepository\Region())->getObjectByQuery($regionQuery);
        if (!$region) {
            throw new \Exception(sprintf('Регион #%s не найден', $regionId));
        }

        // запрос товаров
        $productListQueries = [];
        $descriptionListQueries = [];
        foreach (array_chunk($productUis, $config->curl->queryChunkSize) as $uisInChunk) {
            $productListQuery = new Query\Product\GetListByUiList($uisInChunk, $region->id);
            $curl->prepare($productListQuery);
            $productListQueries[] = $productListQuery;

            $descriptionListQuery = new Query\Product\GetDescriptionListByUiList(
                $uisInChunk,
                [
                    'media'    => true,
                    'category' => true,
                    'label'    => true,
                    'brand'    => true,
                    'tag'      => true,
                ]
            );
            $curl->prepare($descriptionListQuery);
            $descriptionListQueries[] = $descriptionListQuery;
        }

        // запрос списка рейтингов товаров
        $ratingListQuery = null;
        if ($config->productReview->enabled && (bool)$productUis) {
            $ratingListQuery = new Query\Product\Rating\GetListByProductUiList($productUis);
            $curl->prepare($ratingListQuery);
        }

        $curl->execute();

        // список товаров
        $productsById = (bool)$productListQueries ? $productRepository->getIndexedObjectListByQueryList($productListQueries) : [];

        // список рейтингов товаров
        if ($ratingListQuery) {
            $productRepository->setRatingForObjectListByQuery($productsById, $ratingListQuery);
        }

        $productRepository->setDescriptionForListByListQuery($productsById, $descriptionListQueries);

        // ответ
        $response = [
            'productCount' => count($productsById),
            'products'     => array_values($productsById),
        ];

        return new Http\JsonResponse($response);
    }
}
