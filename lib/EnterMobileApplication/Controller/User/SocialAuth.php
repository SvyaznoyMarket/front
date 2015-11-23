<?php

namespace EnterMobileApplication\Controller\User {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\LoggerTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\SessionTrait;
    use EnterAggregator\DebugContainerTrait;
    use EnterQuery as Query;
    use EnterMobileApplication\Controller;
    use EnterMobileApplication\Repository;
    use EnterMobileApplication\Controller\User\SocialAuth\Response;

    class SocialAuth {
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

            $userData = [
                'email'     => null,
                'firstName' => null,
                'lastName'  => null,
                'birthday'  => null,
                'sex'       => null,
            ];
            $userData = array_merge($userData, is_array($request->data['user']) ? $request->data['user'] : []);

            if (!$userData['email']) {
                throw new \Exception('Не передан user.email', Http\Response::STATUS_BAD_REQUEST);
            }
            if (!$userData['firstName']) {
                throw new \Exception('Не передан user.firstName', Http\Response::STATUS_BAD_REQUEST);
            }
            $regionId = is_scalar($request->data['regionId']) ? trim((string)$request->data['regionId']) : null;
            if (!$regionId) {
                throw new \Exception('Не передан regionId', Http\Response::STATUS_BAD_REQUEST);
            }
            $type = is_scalar($request->data['type']) ? trim((string)$request->data['type']) : null;
            if (!$type) {
                throw new \Exception('Не передан type', Http\Response::STATUS_BAD_REQUEST);
            }
            $accessToken = is_scalar($request->data['accessToken']) ? trim((string)$request->data['accessToken']) : null;
            if (!$accessToken) {
                throw new \Exception('Не передан accessToken', Http\Response::STATUS_BAD_REQUEST);
            }
            $userId = is_scalar($request->data['userId']) ? trim((string)$request->data['userId']) : null;
            if (!$userId) {
                throw new \Exception('Не передан userId', Http\Response::STATUS_BAD_REQUEST);
            }

            try {
                $tokenQuery = new Query\User\GetTokenBySocialToken($type, $userId, $accessToken, $userData['email']);
                $tokenQuery->setTimeout($config->coreService->timeout * 3);
                $curl->query($tokenQuery);

                $token = $tokenQuery->getResult();
                if (empty($token)) {
                    throw new \Exception('Не получен token пользователя');
                }

                $response->token = $token;
            } catch (\Exception $e) {
                if ($config->debugLevel) $this->getDebugContainer()->error = $e;

                if (in_array($e->getCode(), [614])) {
                    $user = new \EnterModel\User();
                    $user->regionId = $regionId;
                    $user->email = $userData['email'];
                    $user->firstName = $userData['firstName'];
                    $user->lastName = $userData['lastName'];
                    $user->sex = $userData['sex'];
                    $user->birthday = $userData['birthday'];
                    $createQuery = new Query\User\CreateItemByObject($user, true);
                    $createQuery->setTimeout(2 * $config->coreService->timeout);

                    try {
                        $curl->query($createQuery);
                    } catch (\Exception $e) {
                        $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['user']]);
                    }

                    $token = $createQuery->getResult()['token'];

                    if (empty($token)) {
                        throw new \Exception('Не получен token пользователя');
                    }
                    $response->token = $token;

                    $createProfileQuery = new Query\User\CreateProfile(
                        $token,
                        [
                            'userId'      => $userId,
                            'accessToken' => $accessToken,
                            'type'        => $type,
                            'email'       => $userData['email'],
                        ]
                    );
                    $createProfileQuery->setTimeout(2 * $config->coreService->timeout);
                    try {
                        $curl->query($createProfileQuery);
                    } catch (\Exception $e) {
                        $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['user']]);
                    }
                }

                $response->errors = $this->getErrorsByException($e);
            }

            if (2 == $config->debugLevel) $this->getLogger()->push(['response' => $response]);

            return new Http\JsonResponse($response, (bool)$response->errors ? Http\Response::STATUS_BAD_REQUEST : Http\Response::STATUS_OK);
        }
    }
}

namespace EnterMobileApplication\Controller\User\SocialAuth {
    class Response {
        /** @var string */
        public $token;
        /** @var string[] */
        public $errors = [];
    }
}