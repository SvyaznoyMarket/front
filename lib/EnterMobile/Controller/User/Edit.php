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
use EnterMobile\Model\Page\User\EditProfile as Page;

class Edit {

    use ConfigTrait,
        CurlTrait,
        SessionTrait,
        MustacheRendererTrait,
        DebugContainerTrait;

    public function execute(Http\Request $request) {
        $session = $this->getSession();
        $messageRepository = new \EnterRepository\Message();

        // ид региона
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        // контроллер
        $controller = new \EnterAggregator\Controller\User\EditProfile();
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
        $pageRequest = new \EnterMobile\Repository\Page\User\EditProfile\Request();
        $pageRequest->httpRequest = $request;
        $pageRequest->region = $controllerResponse->region;
        $pageRequest->user = $controllerResponse->user;
        $pageRequest->cart = $controllerResponse->cart;
        $pageRequest->mainMenu = $controllerResponse->mainMenu;

        $pageRequest->formErrors = array_map(
            function(\EnterModel\Message $message) { return $message->name; },
            $messageRepository->getObjectListByHttpSession('editProfile.error', $session)
        );
        $pageRequest->messages = $messageRepository->getObjectListByHttpSession('messages', $session);

        // страница
        $page = new Page();
        (new Repository\Page\User\EditProfile())->buildObjectByRequest($page, $pageRequest);

        // рендер
        $renderer = $this->getRenderer();
        $renderer->setPartials([
            'content' => 'page/private/profile'
        ]);

        $content = $renderer->render('layout/footerless', $page);

        return new Http\Response($content);
    }
}