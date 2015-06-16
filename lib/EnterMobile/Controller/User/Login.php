<?php

namespace EnterMobile\Controller\User;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterAggregator\DebugContainerTrait;
use EnterMobile\Controller;
use EnterMobile\Repository;
use EnterMobile\Routing;
use EnterQuery as Query;
use EnterMobile\Model;
use EnterMobile\Model\Page\User\Login as Page;

class Login {
    use ConfigTrait, LoggerTrait, CurlTrait, RouterTrait, SessionTrait, MustacheRendererTrait, DebugContainerTrait;

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

        $referer = $request->server['HTTP_REFERER'];
        if ($referer) {
            try {
                $route = $router->getRouteByPath(parse_url($referer, PHP_URL_PATH));
                if (
                    $route instanceof Routing\User\Auth
                    || $route instanceof Routing\User\Register
                ) {
                    $referer = null;
                }
            } catch (\Exception $e) {
                // TODO журналирование
            }
        }

        // редирект
        $redirectUrl = (new \EnterMobile\Repository\User())->getRedirectUrlByHttpRequest(
            $request,
            $referer ?: $router->getUrlByRoute(new Routing\User\Index())
        );

        // ид региона
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        // запрос региона
        $regionQuery = new Query\Region\GetItemById($regionId);
        $curl->prepare($regionQuery);

        // запрос пользователя
        $userItemQuery = (new \EnterMobile\Repository\User())->getQueryByHttpRequest($request);
        if ($userItemQuery) {
            $curl->prepare($userItemQuery);
        }

        $curl->execute();

        // регион
        $region = (new \EnterRepository\Region())->getObjectByQuery($regionQuery);

        // запрос дерева категорий для меню
        $categoryTreeQuery = (new \EnterRepository\MainMenu())->getCategoryTreeQuery(1);
        $curl->prepare($categoryTreeQuery);

        // запрос меню
        $mainMenuQuery = new Query\MainMenu\GetItem();
        $curl->prepare($mainMenuQuery);

        $curl->execute();

        // меню
        $mainMenu = (new \EnterRepository\MainMenu())->getObjectByQuery($mainMenuQuery, $categoryTreeQuery);

        // запрос для получения страницы
        $pageRequest = new Repository\Page\User\Login\Request();
        $pageRequest->region = $region;
        $pageRequest->mainMenu = $mainMenu;
        $pageRequest->user = (new \EnterMobile\Repository\User())->getObjectByQuery($userItemQuery);
        $pageRequest->redirectUrl = $redirectUrl;

        $pageRequest->authFormErrors = array_map(function(\EnterModel\Message $message) { return $message->name; }, $messageRepository->getObjectListByHttpSession('authForm.error', $session));
        $pageRequest->authFormFields = (array)$session->flashBag->get('authForm.field');

        $pageRequest->resetFormErrors = array_map(function(\EnterModel\Message $message) { return $message->name; }, $messageRepository->getObjectListByHttpSession('resetForm.error', $session));
        $pageRequest->resetFormFields = (array)$session->flashBag->get('resetForm.field');

        $pageRequest->registerFormErrors = array_map(function(\EnterModel\Message $message) { return $message->name; }, $messageRepository->getObjectListByHttpSession('registerForm.error', $session));
        $pageRequest->registerFormFields = (array)$session->flashBag->get('registerForm.field');

        $pageRequest->messages = $messageRepository->getObjectListByHttpSession('messages', $session);
        $pageRequest->httpRequest = $request;
        //die(json_encode($pageRequest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // страница
        $page = new Page();
        (new Repository\Page\User\Login())->buildObjectByRequest($page, $pageRequest);

        // debug
        if ($config->debugLevel) $this->getDebugContainer()->page = $page;
        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        // рендер
        $renderer = $this->getRenderer();
        $renderer->setPartials([
            'content' => 'page/user-login/content',
        ]);
        $content = $renderer->render('layout/default', $page);

        // http-ответ
        $response = new Http\Response($content);

        return $response;
    }
}