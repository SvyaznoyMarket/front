<?php

namespace EnterMobileApplication\Controller\User\Address;

use Enter\Http;
use EnterMobileApplication\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\DebugContainerTrait;
use EnterQuery as Query;
use EnterModel as Model;
use EnterMobileApplication\Controller;
use EnterMobileApplication\Repository;

class Create {
    use ConfigTrait, LoggerTrait, CurlTrait, SessionTrait, DebugContainerTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();

        $token = is_scalar($request->query['token']) ? (string)$request->query['token'] : null;
        if (!$token) {
            throw new \Exception('Не указан token', Http\Response::STATUS_BAD_REQUEST);
        }
        $addressItem = is_array($request->data['address']) ? $request->data['address'] : null;
        if (!$addressItem) {
            throw new \Exception('Не указан address', Http\Response::STATUS_BAD_REQUEST);
        }

        try {
            $userItemQuery = new Query\User\GetItemByToken($token);
            $curl->prepare($userItemQuery);

            $curl->execute();

            $user = (new \EnterRepository\User())->getObjectByQuery($userItemQuery);
            /*
            if ($user) {
                $response->token = $token;
            }
            */

            $address = new Model\Address();
            foreach ($addressItem as $k => $v) {
                if (!property_exists($address, $k)) continue;

                $address->{$k} = (string)$v;
            }

            $address->userUi = $user->ui;

            $addressItemQuery = new Query\User\Address\CreateItemByUserUi($user->ui, $address);
            $addressItemQuery->setTimeout(3 * $config->crmService->timeout);
            $curl->query($addressItemQuery);

            $id = $addressItemQuery->getResult()['id'];
            $request->query['id'] = $id;
        } catch (\EnterQuery\CoreQueryException $e) {
            if ($config->debugLevel) $this->getDebugContainer()->error = $e;

            if (600 !== $e->getCode()) {
                throw $e;
            }

            $errors = [];
            foreach ($e->getDetail() as $item) {
                if ('title' === $item['propertyPath']) {
                    $item['propertyPath'] = 'name';
                }

                $errors[] = ['code' => $e->getCode(), 'message' => $item['message'], 'field' => $item['propertyPath']];
            }

            return new Http\JsonResponse(
                [
                    'errors' => $errors,
                ],
                Http\Response::STATUS_BAD_REQUEST
            );
        }

        return (new Controller\User\Address\Get())->execute($request);
    }
}
