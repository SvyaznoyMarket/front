<?php

namespace EnterSite\Controller\User;

use Enter\Http;
use EnterCurlQuery as Query;
use EnterRepository as Repository;
use EnterSite\ConfigTrait;
use EnterSite\LoggerTrait;
use EnterSite\CurlClientTrait;
use EnterSite\SessionTrait;
use EnterSite\RouterTrait;
use EnterSite\Controller;
use EnterSite\Model\Form;
use EnterSite\Routing;
use EnterSite\DebugContainerTrait;

class Auth {
    use ConfigTrait, LoggerTrait, CurlClientTrait, RouterTrait, SessionTrait, DebugContainerTrait {
        ConfigTrait::getConfig insteadof LoggerTrait, CurlClientTrait, RouterTrait, SessionTrait, DebugContainerTrait;
        LoggerTrait::getLogger insteadof CurlClientTrait, SessionTrait;
    }

    /**
     * @param Http\Request $request
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurlClient();
        $router = $this->getRouter();
        $session = $this->getSession();
        $messageRepository = new Repository\Message();

        // редирект
        $redirectUrl = (new \EnterRepository\User())->getRedirectUrlByHttpRequest($request, $router->getUrlByRoute(new Routing\User\Index()));
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

            // FIXME: костыль для project13
            $session->set($config->userToken->authCookieName, $token);
            $session->set('authSource', $isEmailAuth ? 'email' : 'phone');

        } catch (\Exception $e) {
            if ($config->debugLevel) $this->getDebugContainer()->error = $e;

            $errors = [];
            switch ($e->getCode()) {
                case 613:
                    $errors['password'] = 'Неверный пароль';
                    break;
                case 614:
                    $errors['username'] = 'Пользователь не найден';
                    break;
                default:
                    $messageRepository->setObjectListToHttpSesion('messages', [
                        new \EnterModel\Message([
                            'name' => 'Произошла ошибка. Возможно неверно указаны логин или пароль',
                            'type' => \EnterModel\Message::TYPE_ERROR
                        ]),
                    ], $session);
            }
            $messageRepository->setObjectListToHttpSesion('authForm.error', $errors, $session);

            return (new Controller\Redirect())->execute($router->getUrlByRoute(new Routing\User\Login()), 302);
            //return (new Controller\User\Login())->execute($request);
        }

        return $response;
    }
}