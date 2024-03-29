<?php

namespace EnterMobile\Repository\Product;

use Enter\Http;
use EnterModel as Model;
use EnterRepository\Product\Sorting as BaseRepository;

class Sorting extends BaseRepository {
    /**
     * @param Http\Request $request
     * @return Model\Product\Sorting|null
     */
    public function getObjectByHttpRequest(Http\Request $request) {
        $sorting = null;

        $data = explode('-', $request->query['sort']);
        if (isset($data[0]) && isset($data[1])) {
            $sorting = new Model\Product\Sorting();
            $sorting->token = $data[0];
            $sorting->direction = $data[1];
        }

        return $sorting;
    }
}