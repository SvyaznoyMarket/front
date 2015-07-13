<?php

namespace EnterMobile\Controller\User;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterAggregator\DebugContainerTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\CurlTrait;
use EnterMobile\Repository;
use EnterQuery as Query;
use EnterMobile\Model\Page\User\ResetPassword as Page;

class Password {

    use ConfigTrait, CurlTrait, SessionTrait, MustacheRendererTrait, DebugContainerTrait;

    public function execute(Http\Request $request) {
        $curl = $this->getCurl();
        $session = $this->getSession();
        $config = $this->getConfig();
        $messageRepository = new \EnterRepository\Message();

        // ид региона
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);
        $controller = new \EnterAggregator\Controller\User\ResetPassword();
        // запрос для контроллера
        $controllerRequest = $controller->createRequest();
        $controllerRequest->regionId = $regionId;
        $controllerRequest->httpRequest = $request;
        // ответ
        $controllerResponse = $controller->execute($controllerRequest);

        if ($controllerResponse->redirect) {
            return $controllerResponse->redirect;
        }

        //запрос для получения страницы
        $pageRequest = new \EnterMobile\Repository\Page\User\PasswordReset\Request();
        $pageRequest->httpRequest = $request;
        $pageRequest->region = $controllerResponse->region;
        $pageRequest->user = $controllerResponse->user;
        $pageRequest->cart = $controllerResponse->cart;
        $pageRequest->mainMenu = $controllerResponse->mainMenu;

        $pageRequest->formErrors = array_map(
                                        function(\EnterModel\Message $message) { return $message->name; },
                                        $messageRepository->getObjectListByHttpSession('changePassword.error', $session)
                                    );
        $pageRequest->messages = $messageRepository->getObjectListByHttpSession('messages', $session);

        $page = new Page();
        (new Repository\Page\User\PasswordReset())->buildObjectByRequest($page, $pageRequest);

        // рендер
        $renderer = $this->getRenderer();
        $renderer->setPartials([
            'content' => 'page/private/password'
        ]);

        $content = $renderer->render('layout/default', $page);

        return new Http\Response($content);
    }
}