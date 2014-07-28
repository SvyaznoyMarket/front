<?php

namespace EnterTerminal\Controller\Search;

use Enter\Http;
use EnterTerminal\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\SessionTrait;
use EnterQuery as Query;
use EnterModel as Model;
use EnterTerminal\Model\Page\Search\Autocomplete as Page;

class Autocomplete {
    use CurlTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $curl = $this->getCurl();

        // ид магазина
        $shopId = (new \EnterTerminal\Repository\Shop())->getIdByHttpRequest($request);

        // запрос магазина
        $query = new Query\Shop\GetItemById($shopId);
        $curl->prepare($query);
        $curl->execute();

        // магазин
        $shop = (new \EnterTerminal\Repository\Shop())->getObjectByQuery($query);
        if (!$shop) {
            throw new \Exception(sprintf('Магазин #%s не найден', $shopId));
        }

        $searchRepository = new \EnterRepository\Search();
        $searchPhrase = $searchRepository->getPhraseByHttpRequest($request);

        // запрос autocomplete
        $query = new Query\Search\GetAutocompleteResultByPhrase($searchPhrase, $shop->regionId);
        $curl->prepare($query);
        $curl->execute();

        // страница
        $page = new Page();
        $autocompleteResult = $searchRepository->getAutocompleteObjectByQuery($query);
        if (null !== $autocompleteResult) {
            $page->categories = $autocompleteResult->categories;
            $page->products = $autocompleteResult->products;
        }

        // response
        return new Http\JsonResponse($page);
    }
}