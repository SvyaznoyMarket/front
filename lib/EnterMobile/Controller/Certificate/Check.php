<?php
namespace EnterMobile\Controller\Certificate;

use Enter\Http;
use EnterAggregator\CurlTrait;
use EnterMobile\ConfigTrait;
use EnterQuery as Query;

class Check {
    use ConfigTrait, CurlTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();

        $code = is_scalar($request->data['code']) ? (string)$request->data['code'] : null;
        $pin = !empty($request->data['pin']) ? (string)$request->data['pin'] : '0000';

        $checkQuery = new Query\Certificate\Check($code, $pin);
        $checkQuery->setTimeout(5 * $config->coreService->timeout);
        $curl->prepare($checkQuery);

        $curl->execute($checkQuery->getTimeout() / 2, 2);

        $checkQuery->getResult();
    }
}
