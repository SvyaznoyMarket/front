<?php

namespace EnterSite\Controller\User;

use Enter\Http;
use EnterSite\ConfigTrait;
use EnterSite\CurlClientTrait;
use EnterSite\RouterTrait;
use EnterSite\Controller;
use EnterSite\Repository;
use EnterSite\Curl\Query;
use EnterSite\Model;
use EnterSite\Model\Form;
use EnterSite\Routing;

class Auth {
    use ConfigTrait, CurlClientTrait, RouterTrait {
        ConfigTrait::getConfig insteadof CurlClientTrait, RouterTrait;
    }

    /**
     * @param Http\Request $request
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurlClient();
        $router = $this->getRouter();

        // редирект
        $redirectUrl = (new Repository\User())->getRedirectUrlByHttpRequest($request, $router->getUrlByRoute(new Routing\User\Login()));
        // http-ответ
        $response = (new Controller\Redirect())->execute($redirectUrl, 302);

        $form = new Form\User\AuthForm();
        $form->username = trim((string)$request->data['username']);
        $form->password = trim((string)$request->data['password']);

        $isEmailAuth = strpos($form->username, '@');
        try {
            $tokenQuery =
                $isEmailAuth
                ? new Query\User\GetTokenByEmail($form->username, $form->password)
                : new Query\User\GetTokenByPhone($form->username, $form->password)
            ;
            $tokenQuery->setTimeout($config->coreService->hugeTimeout);
            $curl->query($tokenQuery);

            $token = $tokenQuery->getResult();
            if (empty($token)) {
                throw new \Exception('Не получен token пользователя');
            }

            // установка cookie
            (new Repository\User())->setTokenToHttpResponse($token, $response);
        } catch (\Exception $e) {
            $request->data['error'] = sprintf('Неверные %s или пароль', $isEmailAuth ? 'email' : 'номер телефона');

            return (new Controller\User\Login())->execute($request);
        }

        return $response;
    }
}