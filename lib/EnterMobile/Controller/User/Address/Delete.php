<?php

namespace EnterMobile\Controller\User\Address;

use Enter\Http;
use EnterAggregator\SessionTrait;
use EnterMobile\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterMobile\Routing;
use EnterMobile\Controller;
use EnterQuery as Query;
use EnterMobile\Model;
use EnterMobile\Repository;

class Delete {
    use Controller\SecurityTrait,
        ConfigTrait,
        RouterTrait,
        LoggerTrait,
        CurlTrait,
        SessionTrait;

    /**
     * @param Http\Request $request
     * @return Http\JsonResponse
     * @throws \Exception
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();
        $session = $this->getSession();

        $error = null;
        try {
            $addressId = $request->query['addressId'] ?: $request->data['addressId'];
            if (!$addressId) {
                throw new \Exception('Не передан addressId', 400);
            }

            $this->getUserToken($session, $request);

            $userItemQuery = (new \EnterMobile\Repository\User())->getQueryBySessionAndHttpRequest($session, $request);
            $curl->prepare($userItemQuery);

            $curl->execute();

            $user = $this->getUser($userItemQuery);

            $removeQuery = new Query\User\Address\DeleteItemByUserUi($user->ui, $addressId);
            $curl->prepare($removeQuery);

            $curl->execute();
        } catch (\Exception $e) {
            $error = $e;
        }

        if ($request->isXmlHttpRequest()) {
            $response = new Http\JsonResponse([
                'success' => (bool)$error,
            ]);
        } else {
            $response = (new \EnterAggregator\Controller\Redirect())->execute(isset($request->server['HTTP_REFERER']) ? $request->server['HTTP_REFERER'] : '/', 302);
        }

        return $response;
    }
}