<?php

namespace EnterMobile\Controller\User\Edit;

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

class Save {

    use ConfigTrait,
        CurlTrait,
        MustacheRendererTrait,
        RouterTrait,
        SessionTrait,
        DebugContainerTrait;

    public function execute(Http\Request $request) {
        $curl = $this->getCurl();
        $session = $this->getSession();
        $router = $this->getRouter();
        $messageRepository = new Repository\Message();

        // редирект
        $redirectUrl = (new \EnterMobile\Repository\User())->getRedirectUrlByHttpRequest($request, $router->getUrlByRoute(new Routing\User\Edit()));
        // http-ответ
        $response = (new \EnterAggregator\Controller\Redirect())->execute($redirectUrl, 302);
        $postData = $request->data->all();

        try {
            $postData = $request->data->all();

            // дату рождения и телефоны нужно привести к стандартному виду
            $postData['birthday'] = date('Y-m-d', strtotime($postData['birthday']));
            $postData['mobile'] = preg_replace('/^\+7/', '8', $postData['mobile']);
            $postData['mobile'] = preg_replace('/[^\d]/', '', $postData['mobile']);

            $postData['phone'] = preg_replace('/^\+7/', '8', $postData['phone']);
            $postData['phone'] = preg_replace('/[^\d]/', '', $postData['phone']);

            // token
            $user = new \EnterMobile\Repository\User();
            $token = $user->getTokenByHttpRequest($request);

            if (!$token) {
                throw new \Exception('Нет токена');
            }

            // одно из полей должно быть заполнено
            $userMail = $postData['email'];
            $userPhone = $postData['mobile'];

            if (!$userMail && !$userPhone) {
                throw new \Exception(
                    'Одно из полей должно быть заполнено',
                    630
                );
            }

            // модель старого пользователя
            $userQuery = $user->getQueryByHttpRequest($request);
            $curl->prepare($userQuery);
            $curl->execute();

            if ($userQuery->getError()) {
                throw new \Exception('Пользователь не получен');
            }

            $userQuery->getData();
            $oldUserModel = new \EnterModel\User($userQuery->getResult());

            // модель нового пользователя
            $newUserModel = new \EnterModel\User($postData);

            // обновление пользователя
            $updateUserQuery = new \EnterQuery\User\UpdateItemByObject($token, $oldUserModel, $newUserModel);
            $curl->prepare($updateUserQuery);
            $curl->execute();

            $updateResult = $updateUserQuery->getResult();

            if (!isset($updateResult['confirmed']) || !$updateResult['confirmed']) {
                throw new \Exception('Не удалось сменить данные пользователя.');
            }

            $messageRepository->setObjectListToHttpSesion('messages', [
                new \EnterModel\Message([
                    'name' => 'Данные успешно изменены',
                    'type' => \EnterModel\Message::TYPE_SUCCESS
                ]),
            ], $session);

        } catch(\Exception $e) {
            $errors = [];

            switch($e->getCode()) {
                case 630:
                    $errors['empty_fields'] = $e->getMessage();
                    break;
                case 690:
                    $errors['mobile'] = $e->getMessage();
            }

            $messageRepository->setObjectListToHttpSesion('editProfile.error', $errors, $session);


            return (new \EnterAggregator\Controller\Redirect())->execute(
                $router->getUrlByRoute(new Routing\User\Edit(),
                    ['redirect_to' => $redirectUrl]),
                302);
        }

        return $response;

    }
}