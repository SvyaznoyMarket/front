<?php

namespace EnterSite\Controller\User;

use Enter\Http;
use EnterCurlQuery as Query;
use EnterRepository as Repository;
use EnterSite\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\RouterTrait;
use EnterSite\Controller;
use EnterSite\Model\Form;
use EnterSite\Routing;
use EnterAggregator\DebugContainerTrait;

class Reset {
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
        //$redirectUrl = (new \EnterRepository\User())->getRedirectUrlByHttpRequest($request, $router->getUrlByRoute(new Routing\User\Index()));
        $redirectUrl = $router->getUrlByRoute(new Routing\User\Login());
        // http-ответ
        $response = (new Controller\Redirect())->execute($redirectUrl, 302);

        $form = new Form\User\ResetForm();
        $form->username = trim((string)$request->data['username']);

        $isEmailAuth = strpos($form->username, '@');
        try {
            $resetQuery =
                $isEmailAuth
                    ? new Query\User\ResetPasswordByEmail($form->username)
                    : new Query\User\ResetPasswordByPhone($form->username)
            ;
            //$resetQuery->setTimeout($config->coreService->hugeTimeout);
            $curl->query($resetQuery);

            $result = $resetQuery->getResult();
            if (!(bool)$result) {
                throw new \Exception('Не получено подтверждение');
            }

            $messageRepository->setObjectListToHttpSesion('messages', [
                new \EnterModel\Message([
                    'name' => 'Пароль отправлен на ваш ' . ($isEmailAuth ? 'email' : 'телефон'),
                    'type' => \EnterModel\Message::TYPE_SUCCESS
                ]),
            ], $session);
        } catch (\Exception $e) {
            if ($config->debugLevel) $this->getDebugContainer()->error = $e;

            $errors = [];
            switch ($e->getCode()) {
                case 601:
                    $errors['username'] = 'Некорректный логин';
                    break;
                case 604:
                    $errors['username'] = 'Пользователь не найден';
                    break;
                default:
                    $messageRepository->setObjectListToHttpSesion('messages', [
                        new \EnterModel\Message([
                            'name' => 'Произошла ошибка. Возможно неверно указан логин',
                            'type' => \EnterModel\Message::TYPE_ERROR
                        ]),
                    ], $session);
            }
            $messageRepository->setObjectListToHttpSesion('resetForm.error', $errors, $session);

            return (new Controller\Redirect())->execute($router->getUrlByRoute(new Routing\User\Login()), 302);
            //return (new Controller\User\Login())->execute($request);
        }

        return $response;
    }
}