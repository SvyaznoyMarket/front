<?php

namespace EnterMobile\Controller\Search;

use Enter\Http;
use EnterAggregator\CurlTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterAggregator\DebugContainerTrait;
use EnterMobile\Controller;
use EnterMobile\Repository;
use EnterQuery as Query;
use EnterAggregator\RouterTrait;
use EnterMobile\Routing;
use EnterMobile\Model;
use EnterMobile\Model\Page\Search\Index as Page;

class Autocomplete {
    use LoggerTrait, RouterTrait, CurlTrait, MustacheRendererTrait, DebugContainerTrait;

    /**
     * @param Http\Request $request
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        $curl = $this->getCurl();
        $searchRepository = new \EnterRepository\Search();
        $mediaRepository = new \EnterRepository\Media();

        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        try {
            $searchPhrase = (new \EnterRepository\Search())->getPhraseByHttpRequest($request, 'q');
            if (!$searchPhrase) {
                throw new \Exception('Не передан параметр q', 400);
            }
        } catch (\Exception $e) {
            return new Http\JsonResponse([
                'code' => $e->getCode(),
                'message' => $e->getMessage()
            ]);
        }

        $searchResultQuery = new Query\Search\GetAutocompleteResultByPhrase($searchPhrase, $regionId);
        $curl->prepare($searchResultQuery);
        $curl->execute();

        $autocompleteResult = $searchRepository->getAutocompleteObjectByQuery($searchResultQuery);

        return new Http\JsonResponse([
            'categories' => array_map(function(\EnterModel\Search\Category $category) use($mediaRepository) {
                return [
                    'name' => $category->name . ' (' . $category->productCount . ')',
                    'link' => $category->link,
                    'image' => $mediaRepository->getSourceObjectByList($category->media->photos, 'main', 'category_163x163')->url,
                ];
            }, $autocompleteResult->categories),
            'products' => array_map(function(\EnterModel\Search\Product $product) use($mediaRepository) {
                return [
                    'name' => $product->name,
                    'link' => $product->link,
                    'image' => $mediaRepository->getSourceObjectByList($product->media->photos, 'main', 'product_60')->url,
                ];
            }, $autocompleteResult->products),
        ]);
    }
}