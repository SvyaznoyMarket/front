<?php

namespace EnterTerminal\Controller\Search;

use Enter\Http;
use EnterAggregator\CurlTrait;
use EnterQuery as Query;
use EnterModel as Model;

class Autocomplete {
    use CurlTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $curl = $this->getCurl();
        $searchRepository = new \EnterRepository\Search();
        $mediaRepository = new \EnterRepository\Media();

        $regionId = (new \EnterTerminal\Repository\Region())->getIdByHttpRequest($request);
        if (!$regionId) {
            throw new \Exception('Не передан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
        }

        $searchPhrase = $searchRepository->getPhraseByHttpRequest($request, 'q');
        if (!$searchPhrase) {
            throw new \Exception('Не передан параметр q', Http\Response::STATUS_BAD_REQUEST);
        }

        $query = new Query\Search\GetAutocompleteResultByPhrase($searchPhrase, $regionId);
        $curl->prepare($query);

        $curl->execute();

        $autocompleteResult = $searchRepository->getAutocompleteObjectByQuery($query);

        return new Http\JsonResponse([
            'categories' => array_map(function(\EnterModel\Search\Category $category) use($mediaRepository) {
                return [
                    'id' => $category->id,
                    'token' => $category->token,
                    'name' => $category->name . ' (' . $category->productCount . ')',
                    'link' => $category->link,
                    'media' => $mediaRepository->getMediaListResponse($category->media, ['photos'], ['main'], ['category_163x163']),
                ];
            }, $autocompleteResult->categories),
            'products' => array_map(function(\EnterModel\Search\Product $product) use($mediaRepository) {
                return [
                    'id' => $product->id,
                    'token' => $product->token,
                    'name' => $product->name,
                    'link' => $product->link,
                    'media' => $mediaRepository->getMediaListResponse($product->media, ['photos'], ['main'], ['product_160']),
                ];
            }, $autocompleteResult->products),
        ]);
    }
}