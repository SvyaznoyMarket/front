<?php

namespace EnterMobile\Controller\User;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\AbTestTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\RouterTrait;
use EnterRepository as Repository;
use EnterQuery as Query;
use EnterMobile\Controller;
use EnterMobile\Model\Form;
use EnterMobile\Routing;
use EnterAggregator\DebugContainerTrait;

class Auth {
    use ConfigTrait, AbTestTrait, LoggerTrait, CurlTrait, RouterTrait, SessionTrait, DebugContainerTrait;

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
        $redirectUrl = (new \EnterMobile\Repository\User())->getRedirectUrlByHttpRequest($request, $router->getUrlByRoute(new Routing\User\Index()));
        // http-ответ
        $response = (new \EnterAggregator\Controller\Redirect())->execute($redirectUrl, 302);

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

            $token = $tokenQuery->getResult()['token'];
            if (empty($token)) {
                throw new \Exception('Не получен token пользователя');
            }

            // установка cookie
            (new \EnterMobile\Repository\User())->setTokenToSessionAndHttpResponse($token, $session, $response);

            $session->set('authSource', $isEmailAuth ? 'email' : 'phone');

            try {
                $controller = new \EnterAggregator\Controller\Cart\Merge();

                $controllerRequest = $controller->createRequest();
                $controllerRequest->regionId = (new Repository\Region())->getIdByHttpRequestCookie($request);
                $controllerRequest->userUi = $tokenQuery->getResult()['ui']; // CORE-3084

                if ($this->getAbTest()->isCoreCartEnabled() && $controllerRequest->regionId && $controllerRequest->userUi) {
                    $controller->execute($controllerRequest);
                }
            } catch (\Exception $e) {
                $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['cart']]);
            }
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

            return (new \EnterAggregator\Controller\Redirect())->execute($router->getUrlByRoute(new Routing\User\Login(), ['redirect_to' => $redirectUrl]), 302);
            //return (new Controller\User\Login())->execute($request);
        }

        return $response;
    }
}