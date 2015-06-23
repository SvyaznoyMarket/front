<?php

namespace EnterMobileApplication\Controller\User {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\SessionTrait;
    use EnterAggregator\DebugContainerTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller;
    use EnterMobileApplication\Repository;
    use EnterMobileApplication\Controller\User\Register\Response;

    class Register {
        use ErrorTrait;
        use ConfigTrait, CurlTrait, SessionTrait, DebugContainerTrait;

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

            // ид региона
            $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request); // FIXME
            if (!$regionId) {
                throw new \Exception('Не указан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
            }

            $willBeSubscribed = (bool)$request->data['subscribe'];

            // запрос региона
            $regionQuery = new Query\Region\GetItemById($regionId);
            $curl->prepare($regionQuery);

            $curl->execute();

            // регион
            $region = (new \EnterRepository\Region())->getObjectByQuery($regionQuery);

            $firstName = is_scalar($request->data['firstName']) ? trim((string)$request->data['firstName']) : null;
            $phone = is_scalar($request->data['phone']) ? trim((string)$request->data['phone']) : null;
            $email = is_scalar($request->data['email']) ? trim((string)$request->data['email']) : null;
            if (!$email && !$phone) {
                throw new \Exception('Не передан email или phone', Http\Response::STATUS_BAD_REQUEST);
            }

            try {
                $user = new Model\User();
                $user->regionId = $region->id;
                $user->firstName = $firstName;
                $user->email = $email;

                $user->phone = $phone;
                $user->phone = preg_replace('/^\+7/', '8', $user->phone);
                $user->phone = preg_replace('/[^\d]/', '', $user->phone);

                $query = new Query\User\CreateItemByObject($user, $willBeSubscribed);
                $query->setTimeout($config->coreService->timeout * 2);
                $curl->query($query);

                $data = $query->getResult();
                if (empty($data['token'])) {
                    throw new \Exception('Не получен token пользователя');
                }

                $response->token = $data['token'];
            } catch (\Exception $e) {
                if ($config->debugLevel) $this->getDebugContainer()->error = $e;

                $response->errors = $this->getErrorsByException($e);
            }

            return new Http\JsonResponse($response, (bool)$response->errors ? Http\Response::STATUS_BAD_REQUEST : Http\Response::STATUS_OK);
        }
    }
}

namespace EnterMobileApplication\Controller\User\Register {
    class Response {
        /** @var string */
        public $token;
        /** @var array[] */
        public $errors = [];
    }
}