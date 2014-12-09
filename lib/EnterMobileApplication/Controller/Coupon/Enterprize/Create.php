<?php

namespace EnterMobileApplication\Controller\Coupon\Enterprize {

    use Enter\Http;
    use EnterMobileApplication\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterAggregator\LoggerTrait;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\Coupon\Enterprize\Create\Response;

    class Create {
        use ConfigTrait, LoggerTrait, CurlTrait;

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
                throw new \Exception('Не указан token');
            }

            // запрос пользователя
            $userItemQuery = new Query\User\GetItemByToken($token);
            $curl->prepare($userItemQuery);

            // запрос купона


            $curl->execute();

            // получение пользователя
            $user = (new \EnterRepository\User())->getObjectByQuery($userItemQuery);
            if (!$user) {
                throw new \Exception('Пользователь не авторизован', Http\Response::STATUS_UNAUTHORIZED); // FIXME
            }
            $response->token = $token;

            //$createCouponQuery = new Query\Coupon\Enterprize\Create($token, $user, );


            // response
            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterMobileApplication\Controller\Coupon\Enterprize\Create {
    use EnterModel as Model;

    class Response {
        /** @var string|null */
        public $token;
        /** @var array[] */
        public $errors = [];
    }
}