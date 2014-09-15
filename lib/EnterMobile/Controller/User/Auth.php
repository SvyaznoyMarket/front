<?php

namespace EnterMobile\Controller\User;

use Enter\Http;
use EnterQuery as Query;
use EnterRepository as Repository;
use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\RouterTrait;
use EnterMobile\Controller;
use EnterMobile\Model\Form;
use EnterMobile\Routing;
use EnterAggregator\DebugContainerTrait;

class Auth {
    use ConfigTrait, LoggerTrait, CurlTrait, RouterTrait, SessionTrait, DebugContainerTrait;

    /**
     * @param Http\Request $request
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();
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
            $tokenQuery->setTimeout(2 * $config->coreService->timeout);
            $curl->query($tokenQuery);

            $token = $tokenQuery->getResult();
            if (empty($token)) {
                throw new \Exception('Не получен token пользователя');
            }

            // установка cookie
            (new \EnterRepository\User())->setTokenToHttpResponse($token, $response);

            // FIXME: костыль для project13
            $session->set($config->userToken->authName, $token);
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
                            'name' => 'Ошибка. Неверно указаны логин или пароль',
                            'type' => \EnterModel\Message::TYPE_ERROR
                        ]),
                    ], $session);
            }
            $messageRepository->setObjectListToHttpSesion('authForm.error', $errors, $session);

            $session->flashBag->set('authForm.field', [
                'username' => $form->username,
            ]);

            return (new Controller\Redirect())->execute($router->getUrlByRoute(new Routing\User\Login(), ['redirect_to' => $redirectUrl]), 302);
            //return (new Controller\User\Login())->execute($request);
        }

        return $response;
    }
}