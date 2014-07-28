<?php

namespace EnterMobile\Controller\User;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\DebugContainerTrait;
use EnterAggregator\RouterTrait;
use EnterMobile\Controller;
use EnterMobile\Repository;
use EnterQuery as Query;
use EnterModel as Model;
use EnterMobile\Model\Form;
use EnterMobile\Routing;
use EnterAggregator\SessionTrait;

class Register {
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
        $messageRepository = new \EnterRepository\Message();

        // редирект
        //$redirectUrl = (new \EnterRepository\User())->getRedirectUrlByHttpRequest($request, $router->getUrlByRoute(new Routing\User\Login()));
        $redirectUrl = $router->getUrlByRoute(new Routing\User\Login());
        // http-ответ
        $response = (new Controller\Redirect())->execute($redirectUrl, 302);

        $form = new Form\User\RegisterForm();
        $form->name = trim((string)$request->data['name']);
        $form->email = trim((string)$request->data['email']);
        // phone
        $form->phone = trim((string)$request->data['phone']);
        $form->phone = preg_replace('/^\+7/', '8', $form->phone);
        $form->phone = preg_replace('/[^\d]/', '', $form->phone);

        $form->subscribe = !empty($request->data['subscribe']);

        try {
            $user = new Model\User();
            $user->regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);
            $user->firstName = $form->name;
            $user->email = $form->email;
            $user->phone = $form->phone;

            $createItemQuery = new Query\User\CreateItemByObject($user);
            $createItemQuery->setTimeout($config->coreService->hugeTimeout);
            $curl->query($createItemQuery);

            $result = $createItemQuery->getResult();

            if (empty($result['id'])) {
                throw new \Exception('Не удалось создать пользователя');
            }

            $messageRepository->setObjectListToHttpSesion('messages', [
                new \EnterModel\Message([
                    'name' => 'Пароль отправлен на ваш ' . ($form->email ? 'email' : 'телефон'),
                    'type' => \EnterModel\Message::TYPE_SUCCESS
                ]),
            ], $session);
        } catch (\Exception $e) {
            if ($config->debugLevel) $this->getDebugContainer()->error = $e;

            $errors = [];
            switch ($e->getCode()) {
                case 680:
                    $errors['email'] = $errors['phone'] = 'Неверные email или телефон';
                    break;
                case 684:
                    $errors['email'] = $e->getMessage();
                    break;
                case 686:
                    $errors['phone'] = $e->getMessage();
                    break;
                case 689: case 690:
                    $errors['name'] = $e->getMessage();
                    break;
                default:
                    $messageRepository->setObjectListToHttpSesion('messages', [
                        new \EnterModel\Message([
                            'name' => 'Произошла ошибка. Возможно неверно указаны данные',
                            'type' => \EnterModel\Message::TYPE_ERROR
                        ]),
                    ], $session);
            }
            $messageRepository->setObjectListToHttpSesion('registerForm.error', $errors, $session);

            return (new Controller\User\Login())->execute($request);
        }

        return $response;
    }
}