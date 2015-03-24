<?php

namespace Enter1C\Controller;

use Enter\Http;
use Enter1C\Http\XmlResponse;
use EnterQuery as Query;
use EnterModel as Model;
use Enter1C\Repository;

class Index {
    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return XmlResponse
     */
    public function execute(Http\Request $request) {
        return new XmlResponse(['message' => 'Привет']);
    }
}