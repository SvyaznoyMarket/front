<?php

namespace EnterMobile\Controller\Search;

use Enter\Http;
use EnterMobile\ConfigTrait;
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
    use ConfigTrait, LoggerTrait, RouterTrait, CurlTrait, MustacheRendererTrait, DebugContainerTrait;

    /**
     * @param Http\Request $request
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        $curl = $this->getCurl();
        $searchRepository = new \EnterMobile\Repository\Search();

        // ид региона
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        // поисковая строка
        try {
            $searchPhrase = (new \EnterRepository\Search())->getPhraseByHttpRequest($request);
            if (!$searchPhrase) {
                throw new \Exception('Bad Request', 400);
            }
        } catch (\Exception $e) {
            return new Http\JsonResponse([
                'code' => $e->getCode(),
                'message' => $e->getMessage()
            ]);
        }

        // запрос региона
        $regionQuery = new Query\Region\GetItemById($regionId);
        $curl->prepare($regionQuery);
        $curl->execute();

        // регион
        $region = (new \EnterRepository\Region())->getObjectByQuery($regionQuery);

        $searchResultQuery = new Query\Search\GetAutocompleteResultByPhrase($searchPhrase, $region->id);
        $curl->prepare($searchResultQuery);
        $curl->execute();

        $autocompleteResponse = $searchRepository->getAutocompleteObjectByQuery($searchResultQuery);

        return new Http\JsonResponse($autocompleteResponse);
    }
}