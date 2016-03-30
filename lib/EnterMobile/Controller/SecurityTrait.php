<?php

namespace EnterMobile\Controller;

use Enter\Http;
use Enter\Curl\Query as Query;
use EnterModel as Model;
use EnterMobile\Repository;

trait SecurityTrait {
    /**
     * @param Http\Session $session
     * @param Http\Request $request
     * @return string
     * @throws \Exception
     */
    public function getUserToken(Http\Session $session, Http\Request $request) {
        $userToken = (new Repository\User())->getTokenBySessionAndHttpRequest($session, $request);
        if (empty($userToken)) {
            throw new \Exception('Доступ запрещен', Http\Response::STATUS_FORBIDDEN);
        }

        return $userToken;
    }

    /**
     * @param Query $query
     * @return Model\User
     * @throws \Exception
     */
    public function getUser(Query $query) {
        $user = (new Repository\User())->getObjectByQuery($query);
        if (!$user) {
            throw new \Exception('Пользователь не авторизован', Http\Response::STATUS_UNAUTHORIZED);
        }

        return $user;
    }
}