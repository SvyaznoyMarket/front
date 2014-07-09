<?php

namespace EnterSite\Action;

use Enter\Http;
use EnterSite\ConfigTrait;
use EnterSite\LoggerTrait;
use EnterRepository as Repository;
use EnterModel as Model;

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

        // если партнера нет в http.request.query и нет в http.request.cookie
        if (!$partner && !$partnerRepository->getObjectByHttpRequestCookie($request)) {
            $refererHost = $request->server['HTTP_REFERER'] ? parse_url($request->server['HTTP_REFERER'], PHP_URL_HOST): null;
            if ($refererHost) {
                $partner = new Model\Partner();
                $partner->token = $refererHost;

                foreach ($partnerRepository->getFreeHosts() as $host) {
                    if (false === strpos($refererHost, $host)) continue;

                    $partner = new Model\Partner();
                    $partner->token = $host;
                    break;
                }

                $partnerRepository->setTokenToHttpResponse($partner, $response);
            }
        }
    }
}