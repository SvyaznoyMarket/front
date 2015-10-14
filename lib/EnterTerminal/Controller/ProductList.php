<?php

namespace EnterTerminal\Controller;

use Enter\Http;
use EnterTerminal\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterQuery as Query;
use EnterModel as Model;
use EnterTerminal\Controller;

class ProductList {
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

        // список айдишников
        $productIds = [];
        foreach ((array)$request->query['productIds'] as $productId) {
            if (is_scalar($productId)) {
                $productIds[] = (string)$productId;
            }
        }
        if (!(bool)$productIds) {
            throw new \Exception('Не передан productIds', Http\Response::STATUS_BAD_REQUEST);
        }

        // запрос региона
        $regionQuery = new Query\Region\GetItemById($regionId);
        $curl->prepare($regionQuery);

        $curl->execute();

        // регион
        $region = (new \EnterRepository\Region())->getObjectByQuery($regionQuery);
        if (!$region) {
            throw new \Exception(sprintf('Регион #%s не найден', $regionId));
        }

        // запрос товаров
        $productListQueries = [];
        $descriptionListQueries = [];
        foreach (array_chunk($productIds, $config->curl->queryChunkSize) as $idsInChunk) {
            $productListQuery = new Query\Product\GetListByIdList($idsInChunk, $region->id, ['related' => false]);
            $curl->prepare($productListQuery);
            $productListQueries[] = $productListQuery;

            $descriptionListQuery = new Query\Product\GetDescriptionListByIdList(
                $idsInChunk,
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
        if ($config->productReview->enabled && (bool)$productIds) {
            $ratingListQuery = new Query\Product\Rating\GetListByProductIdList($productIds);
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
