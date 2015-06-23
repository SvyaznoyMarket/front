<?php

namespace EnterMobile\Repository;

use Enter\Http;
use Enter\Curl\Query;
use EnterAggregator\ConfigTrait;
use EnterMobile\Model\Search as Model;

class Search {
    use ConfigTrait;

    /**
     * @param Query $query
     * @return \EnterMobile\Model\Search\AutocompleteResult
     */
    public function getAutocompleteObjectByQuery(Query $query) {
        $item = $query->getResult();
        if ($item) {
            return new Model\AutocompleteResult($item);
        }

        return null;
    }
}