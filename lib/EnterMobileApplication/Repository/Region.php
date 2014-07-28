<?php

namespace EnterMobileApplication\Repository;

use Enter\Http;
use EnterRepository\Region as BaseRepository;

class Region extends BaseRepository {
    /**
     * @param Http\Request $request
     * @return string|null
     */
    public function getIdByHttpRequest(Http\Request $request) {
        return is_scalar($request->query['regionId']) ? (string)$request->query['regionId'] : null;
    }
}