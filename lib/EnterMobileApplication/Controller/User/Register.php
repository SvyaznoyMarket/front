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
        use ConfigTrait, CurlTrait, SessionTrait, DebugContainerTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();
            $session = $this->getSession();

            // ответ
            $response = new Response();

            // ид региона
            $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request); // FIXME
            if (!$regionId) {
                throw new \Exception('Не указан параметр regionId');
            }

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
                throw new \Exception('Не передан email или phone');
            }

            try {
                $user = new Model\User();
                $user->regionId = $region->id;
                $user->firstName = $firstName;
                $user->email = $email;
                $user->phone = $phone;
                $user->phone = preg_replace('/^\+7/', '8', $user->phone);
                $user->phone = preg_replace('/[^\d]/', '', $user->phone);

                $query = new Query\User\CreateItemByObject($user);
                $query->setTimeout($config->coreService->timeout * 2);
                $curl->query($query);

                $data = $query->getResult();
                if (empty($data['token'])) {
                    throw new \Exception('Не получен token пользователя');
                }

                $response->token = $data['token'];
            } catch (\Exception $e) {
                if ($config->debugLevel) $this->getDebugContainer()->error = $e;

                switch ($e->getCode()) {
                    case 684:
                        $response->errors[] = ['code' => $e->getCode(), 'message' => 'Такой email уже занят', 'field' => 'email'];
                        break;
                    case 689:
                        $response->errors[] = ['code' => $e->getCode(), 'message' => 'Неправильный email', 'field' => 'email'];
                        break;
                    case 686:
                        $response->errors[] = ['code' => $e->getCode(), 'message' => 'Такой номер уже занят', 'field' => 'phone'];
                        break;
                    case 690:
                        $response->errors[] = ['code' => $e->getCode(), 'message' => 'Неправильный телефон', 'field' => 'phone'];
                        break;
                    case 613:
                        $response->errors[] = ['code' => $e->getCode(), 'message' => 'Неверный пароль', 'field' => 'password'];
                        break;
                    case 614:
                        $response->errors[] = ['code' => $e->getCode(), 'message' => 'Пользователь не найден', 'field' => 'username'];
                        break;
                    case 609:
                        $response->errors[] = ['code' => $e->getCode(), 'message' => 'Не удалось создать пользователя', 'field' => null];
                        break;
                    default:
                        $response->errors[] = ['code' => $e->getCode(), 'message' => 'Произошла ошибка. Возможно неверно указаны логин или пароль', 'field' => null];
                }
            }

            return new Http\JsonResponse($response, (bool)$response->errors ? Http\Response::STATUS_BAD_REQUEST : Http\Response::STATUS_OK);
        }
    }
}

namespace EnterMobileApplication\Controller\User\Register {
    class Response {
        /** @var string */
        public $token;
        /** @var string[] */
        public $errors = [];
    }
}