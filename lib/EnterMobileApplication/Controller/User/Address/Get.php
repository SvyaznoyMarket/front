<?php

namespace EnterMobileApplication\Controller\User\Address {

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
    use EnterMobileApplication\Controller\User\Address\Get\Response;

    class Get {
        use ConfigTrait, LoggerTrait, CurlTrait, SessionTrait, DebugContainerTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();

            // ответ
            $response = new Response();

            $token = is_scalar($request->query['token']) ? (string)$request->query['token'] : null;
            if (!$token) {
                throw new \Exception('Не указан token', Http\Response::STATUS_BAD_REQUEST);
            }

            $id = isset($request->query['id']) ? $request->query['id'] : null;

            try {
                $userItemQuery = new Query\User\GetItemByToken($token);
                $curl->prepare($userItemQuery);

                $curl->execute();

                $user = (new \EnterRepository\User())->getObjectByQuery($userItemQuery);
                if ($user) {
                    $response->token = $token;
                }

                if ($id) {
                    $addressQuery = new Query\User\Address\GetItemByUserUi($user->ui, $id);
                    $addressQuery->setTimeout(2 * $config->crmService->timeout);
                    $curl->prepare($addressQuery);
                } else {
                    $addressQuery = new Query\User\Address\GetListByUserUi($user->ui, ['priority' => 'DESC']);
                    $addressQuery->setTimeout(2 * $config->crmService->timeout);
                    $curl->prepare($addressQuery);
                }

                $curl->execute();

                $addressListResult = $addressQuery->getResult();
                if (isset($addressListResult['id'])) {
                    $addressListResult = [$addressListResult];
                }

                $addresses = [];
                $regionsById = [];
                foreach ($addressListResult as $item) {
                    if (!isset($item['id'])) continue;

                    $address = new Model\Address($item);
                    $addresses[] = $address;
                    $regionsById[$address->regionId] = null;
                }

                if ($regionsById) {
                    $addressQuery = new Query\Region\GetListByIdList(array_keys($regionsById));
                    $curl->prepare($addressQuery);
                    $curl->execute();

                    $regionsById = (new \EnterRepository\Region())->getIndexedByIdObjectListByQuery($addressQuery);
                }

                $response->addresses = array_map(function(Model\Address $address) use($regionsById) {
                    return [
                        'id' => $address->id,
                        'userUi' => $address->userUi,
                        'type' => $address->type,
                        'kladrId' => $address->kladrId,
                        'regionId' => $address->regionId,
                        'regionName' => isset($regionsById[$address->regionId]) ? $regionsById[$address->regionId]->name : '',
                        'zipCode' => $address->zipCode,
                        'address' => $address->address,
                        'street' => $address->street,
                        'streetType' => $address->streetType,
                        'building' => $address->building,
                        'apartment' => $address->apartment,
                        'description' => $address->description,
                    ];
                }, $addresses);
            } catch (\Exception $e) {
                if ($config->debugLevel) $this->getDebugContainer()->error = $e;
            }

            if (2 == $config->debugLevel) $this->getLogger()->push(['response' => $response]);

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterMobileApplication\Controller\User\Address\Get {
    use EnterModel as Model;

    class Response {
        /** @var string */
        public $token;
        /** @var array */
        public $addresses = [];
    }
}