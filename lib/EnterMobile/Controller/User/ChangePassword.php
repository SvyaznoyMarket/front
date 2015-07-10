<?php

namespace EnterMobile\Controller\User;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterAggregator\DebugContainerTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\RouterTrait;
use EnterRepository as Repository;
use EnterQuery as Query;
use EnterMobile\Routing;
use EnterMobile\Model\Page\DefaultPage as Page;

class ChangePassword {

    use ConfigTrait, CurlTrait, MustacheRendererTrait, RouterTrait, SessionTrait, DebugContainerTrait;

    public function execute(Http\Request $request) {
        $curl = $this->getCurl();
        $session = $this->getSession();
        $router = $this->getRouter();
        $messageRepository = new Repository\Message();

        // редирект
        $redirectUrl = (new \EnterMobile\Repository\User())->getRedirectUrlByHttpRequest($request, $router->getUrlByRoute(new Routing\User\Password()));
        // http-ответ
        $response = (new \EnterAggregator\Controller\Redirect())->execute($redirectUrl, 302);

        $postData = $request->data->all();

        $oldPassword = trim((string)$postData['old-password']);
        $newPassword = trim((string)$postData['new-password']);
        $confirmPassword = trim((string)$postData['confirm-password']);

        try {

            if (!$oldPassword) {
                throw new \Exception(
                    'Не заполнено поле старого пароля',
                    630
                );
            }

            if (!$newPassword) {
                throw new \Exception(
                    'Не заполнено поле нового пароля',
                    631
                );
            }

            if (!$confirmPassword) {
                throw new \Exception(
                    'Не заполнено подтверждение пароля',
                    632
                );
            }

            if ($oldPassword == $newPassword) {
                throw new \Exception(
                    'Старый пароль совпадает с новым паролем',
                    633
                );
            }

            if ($newPassword != $confirmPassword) {
                throw new \Exception(
                    'Новые пароли не совпадают',
                    634
                );
            }

            $user = new \EnterMobile\Repository\User();
            $token = $user->getTokenByHttpRequest($request);
            if (empty($token)) {
                throw new \Exception('Не получен токен пользователя');
            }

            $changePassQuery = new Query\User\UpdatePassword($token, $oldPassword, $newPassword);
            $curl->prepare($changePassQuery);
            $curl->execute();

            if ($changePassQuery->getError()) {
                throw new \Exception(
                    $changePassQuery->getError()->getMessage(),
                    $changePassQuery->getError()->getCode()
                );
            }

            $messageRepository->setObjectListToHttpSesion('messages', [
                new \EnterModel\Message([
                    'name' => 'Пароль успешно изменен',
                    'type' => \EnterModel\Message::TYPE_SUCCESS
                ]),
            ], $session);

        } catch (\Exception $e) {
            $errors = [];

            switch($e->getCode()) {
                case 613:
                    $errors['password'] = $e->getMessage();
                    break;
                case 630:
                    $errors['old-password'] = $e->getMessage();
                    break;
                case 631:
                    $errors['new-password'] = $e->getMessage();
                    break;
                case 632:
                    $errors['confirm-password'] = $e->getMessage();
                    break;
                case 633:
                    $errors['password-old-new'] = $e->getMessage();
                    break;
                case 634:
                    $errors['password-identity'] = $e->getMessage();
                    break;
            }

            $messageRepository->setObjectListToHttpSesion('changePassword.error', $errors, $session);

            return (new \EnterAggregator\Controller\Redirect())->execute(
                        $router->getUrlByRoute(new Routing\User\Password(),
                        ['redirect_to' => $redirectUrl]),
                        302);

        }

        return $response;
    }
}