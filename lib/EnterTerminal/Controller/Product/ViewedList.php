<?php

namespace EnterTerminal\Controller\Product;

use Enter\Http;
use EnterAggregator\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterQuery as Query;
use EnterModel as Model;

class ViewedList {
    use ConfigTrait, CurlTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
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

        // запрос региона
        $regionQuery = new Query\Region\GetItemById($regionId);
        $curl->prepare($regionQuery);

        $viewedListQuery = new Query\Event\GetProductView();
        $curl->prepare($viewedListQuery);

        $curl->execute();

        // регион
        $region = (new \EnterRepository\Region())->getObjectByQuery($regionQuery);
        if (!$region) {
            throw new \Exception(sprintf('Регион #%s не найден', $regionId));
        }

        // ответ
        $response = [
            'productCount' => 0,
            'products'     => [],
        ];

        $productUis = array_column($viewedListQuery->getResult()['products'], 'uid');
        if ($productUis) {
            // запрос товаров
            $productListQueries = [];
            $descriptionListQueries = [];
            foreach (array_chunk($productUis, $config->curl->queryChunkSize) as $uisInChunk) {
                $productListQuery = new Query\Product\GetListByUiList($uisInChunk, $region->id, ['related' => false]);
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
            $ratingListQuery = new Query\Product\Rating\GetListByProductUiList($productUis);
            $curl->prepare($ratingListQuery);

            $curl->execute();

            // список товаров
            $productsById = (bool)$productListQueries ? $productRepository->getIndexedObjectListByQueryList($productListQueries, $descriptionListQueries) : [];

            // список рейтингов товаров
            if ($ratingListQuery) {
                $productRepository->setRatingForObjectListByQuery($productsById, $ratingListQuery);
            }

            // ответ
            $response = [
                'productCount' => count($productsById),
                'products'     => array_values($productsById),
            ];
        }

        return new Http\JsonResponse($response);
    }
}
