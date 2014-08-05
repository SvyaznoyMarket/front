<?php

namespace EnterRepository;

use Enter\Http;
use Enter\Curl\Query;
use EnterAggregator\ConfigTrait;
use EnterModel as Model;

class Search {
    use ConfigTrait;

    public function getPhraseByHttpRequest(Http\Request $request, $key = 'q') {
        $phrase = (string)$request->query[$key];

        $encode = mb_detect_encoding($phrase, array('UTF-8', 'Windows-1251'), true);
        switch ($encode) {
            case 'Windows-1251': {
                $phrase = iconv('Windows-1251', 'UTF-8', $phrase);
            }
        }
        $phrase = trim(preg_replace('/[^\wА-Яа-я-]+/u', ' ', $phrase));

        if (empty($phrase) || (mb_strlen($phrase) < $this->getConfig()->search->minPhraseLength)) {
            $phrase = null;
        }

        return $phrase;
    }

    /**
     * @param Query $query
     * @return Model\SearchResult|null
     */
    public function getObjectByQuery(Query $query) {
        $searchResult = null;

        if ($item = $query->getResult()) {
            $searchResult = new Model\SearchResult($item);
        }

        return $searchResult;
    }

    /**
     * @param Query $query
     * @return Model\Search\AutocompleteResult
     */
    public function getAutocompleteObjectByQuery(Query $query) {
        $item = $query->getResult();
        if ($item) {
            return new Model\Search\AutocompleteResult($item);
        }

        return null;
    }
}