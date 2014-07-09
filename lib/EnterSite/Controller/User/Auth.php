<?php

namespace EnterSite\Controller\User;

use Enter\Http;
use EnterSite\ConfigTrait;
use EnterSite\CurlClientTrait;
use EnterSite\RouterTrait;
use EnterSite\Controller;
use EnterCurlQuery as Query;
use EnterSite\Model\Form;
use EnterSite\Routing;
use EnterSite\DebugContainerTrait;

class Auth {
    use ConfigTrait, CurlClientTrait, RouterTrait, DebugContainerTrait {
        ConfigTrait::getConfig insteadof CurlClientTrait, RouterTrait, DebugContainerTrait;
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
        $redirectUrl = (new \EnterRepository\User())->getRedirectUrlByHttpRequest($request, $router->getUrlByRoute(new Routing\User\Login()));
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
            (new \EnterRepository\User())->setTokenToHttpResponse($token, $response);
        } catch (\Exception $e) {
            if ($config->debugLevel) $this->getDebugContainer()->error = $e;

            $formErrors = [];
            switch ($e->getCode()) {
                case 613:
                    $formErrors['password'] = 'Неверный пароль'; //sprintf('Неверные %s или пароль', $isEmailAuth ? 'email' : 'номер телефона');
                    break;
                case 614:
                    $formErrors['username'] = 'Пользователь не найден';
                    break;
                default:
                    $request->data['error'] = 'Произошла ошибка. Возможно неверно указаны логин или пароль';
            }
            $request->data['authForm_error'] = $formErrors;

            return (new Controller\User\Login())->execute($request);
        }

        return $response;
    }
}