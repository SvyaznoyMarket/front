<?php

namespace EnterMobile\Action;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterRepository as Repository;
use EnterModel as Model;

class CheckPartner {
    use ConfigTrait, LoggerTrait;

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

                foreach ($partnerRepository->getFreeSources() as $freeSource) {
                    if (!empty($freeSource['host_name']) && (false === strpos($refererHost, $freeSource['host_name']))) {
                        continue;
                    }

                    $partner = new Model\Partner($freeSource);
                    break;
                }

                $partnerRepository->setTokenToHttpResponse($partner, $response);
            }
        }
    }
}