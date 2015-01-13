<?php

namespace EnterTerminal\Repository;

use Enter\Http;
use EnterRepository\Region as BaseRepository;

class Region extends BaseRepository {
    /**
     * @param Http\Request $request
     * @return string|null
     */
    public function getIdByHttpRequest(Http\Request $request) {
        $id = is_scalar($request->query['regionId']) ? trim((string)$request->query['regionId']) : null;
        if (!$id) {
            $id = null;
        }

        return $id;
    }
}