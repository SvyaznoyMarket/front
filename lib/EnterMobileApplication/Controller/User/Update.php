<?php

namespace EnterMobileApplication\Controller\User {

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
    use EnterMobileApplication\Controller\User\Update\Response;

    class Update {
        use ErrorTrait;
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

            try {
                $userItemQuery = new Query\User\GetItemByToken($token);
                $curl->prepare($userItemQuery)->execute();

                $user = (new \EnterRepository\User())->getObjectByQuery($userItemQuery);

                if ($user) {
                    $response->token = $token;
                    $response->user = $user;

                    $newUser = new Model\User();

                    if (isset($request->data['email'])) {
                        $newUser->email = $request->data['email'] ? trim((string)$request->data['email']) : null;
                    }
                    if (isset($request->data['phone'])) {
                        $newUser->phone = $request->data['phone'] ? trim((string)$request->data['phone']) : null;
                    }
                    if (isset($request->data['firstName'])) {
                        $newUser->firstName = $request->data['firstName'] ? trim((string)$request->data['firstName']) : null;
                    }
                    if (isset($request->data['lastName'])) {
                        $newUser->lastName = $request->data['firstName'] ? trim((string)$request->data['lastName']) : null;
                    }
                    if (isset($request->data['middleName'])) {
                        $newUser->middleName = $request->data['middleName'] ? trim((string)$request->data['middleName']) : null;
                    }
                    if (isset($request->data['sex'])) {
                        $newUser->sex = $request->data['sex'] ? (int)$request->data['sex'] : null;
                    }
                    if (isset($request->data['birthday'])) {
                        $newUser->birthday = $request->data['birthday'] ? trim((string)$request->data['birthday']) : null;
                    }
                    if (isset($request->data['occupation'])) {
                        $newUser->occupation = $request->data['occupation'] ? trim((string)$request->data['occupation']) : null;
                    }
                    if (isset($request->data['homePhone'])) {
                        $newUser->homePhone = $request->data['homePhone'] ? trim((string)$request->data['homePhone']) : null;
                    }
                    if (isset($request->data['svyaznoyClubCardNumber'])) {
                        $newUser->svyaznoyClubCardNumber = $request->data['svyaznoyClubCardNumber'] ? trim((string)$request->data['svyaznoyClubCardNumber']) : null;
                    }

                    $updateQuery = new Query\User\UpdateItemByObject($token, $user, $newUser);
                    $curl->query($updateQuery);

                    $userData = $updateQuery->getResult();
                    if ($userData) {
                        $userItemQuery = new Query\User\GetItemByToken($token);
                        $curl->prepare($userItemQuery)->execute();

                        $response->user = (new \EnterRepository\User())->getObjectByQuery($userItemQuery);
                    }
                }

            } catch (\Exception $e) {
                if ($config->debugLevel) $this->getDebugContainer()->error = $e;

                $response->errors = $this->getErrorsByException($e);
            }

            if (2 == $config->debugLevel) $this->getLogger()->push(['response' => $response]);

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterMobileApplication\Controller\User\Update {
    use EnterModel as Model;

    class Response {
        /** @var string */
        public $token;
        /** @var Model\User|null */
        public $user;
        /** @var array */
        public $errors = [];
    }
}