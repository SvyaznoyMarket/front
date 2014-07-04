<?php

namespace EnterSite\Controller\User;

use Enter\Http;
use EnterSite\ConfigTrait;
use EnterSite\CurlClientTrait;
use EnterSite\RouterTrait;
use EnterSite\Controller;
use EnterSite\Repository;
use EnterCurlQuery as Query;
use EnterModel as Model;
use EnterSite\Model\Form;
use EnterSite\Routing;

class Register {
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
        $redirectUrl = (new \EnterRepository\User())->getRedirectUrlByHttpRequest($request, $router->getUrlByRoute(new Routing\User\Login()));
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

            try {
                $result = $createItemQuery->getResult();
            } catch (\Exception $e) {
                $result = null;
            }

            if (empty($result['id'])) {
                throw new \Exception('Не удалось создать пользователя');
            }
        } catch (\Exception $e) {
            $request->data['error'] = $e->getMessage();

            return (new Controller\User\Login())->execute($request);
        }

        return $response;
    }
}