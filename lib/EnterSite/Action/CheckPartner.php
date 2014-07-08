<?php

namespace EnterSite\Action;

use Enter\Http;
use EnterSite\ConfigTrait;
use EnterSite\LoggerTrait;
use EnterRepository as Repository;

class CheckPartner {
    use ConfigTrait, LoggerTrait {
        ConfigTrait::getConfig insteadof LoggerTrait;
    }

    /**
     * Установка кук для партнера
     * @param Http\Request $request
     * @param Http\Response $response
     * @return null
     */
    public function execute(Http\Request $request, Http\Response &$response) {
        $config = $this->getConfig();
        $logger = $this->getLogger();

        $partnerRepository = new Repository\Partner();

        // получение партнера по http.request.query (!)
        $partner = $partnerRepository->getObjectByHttpRequestQuery($request);

        // если партнер обнаружен в http.request.query (или, в GET-параметрах), то обновляем куку "Последний партнер"
        if ($partner) {
            $partnerRepository->setTokenToHttpResponse($partner, $response);
        }
    }
}