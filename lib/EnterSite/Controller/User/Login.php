<?php

namespace EnterSite\Controller\User;

use Enter\Http;
use EnterSite\ConfigTrait;
use EnterSite\CurlClientTrait;
use EnterSite\LoggerTrait;
use EnterSite\RouterTrait;
use EnterSite\SessionTrait;
use EnterSite\MustacheRendererTrait;
use EnterSite\DebugContainerTrait;
use EnterSite\Controller;
use EnterSite\Repository;
use EnterSite\Routing;
use EnterCurlQuery as Query;
use EnterSite\Model;
use EnterSite\Model\Page\User\Login as Page;

class Login {
    use ConfigTrait, LoggerTrait, CurlClientTrait, RouterTrait, SessionTrait, MustacheRendererTrait, DebugContainerTrait {
        ConfigTrait::getConfig insteadof LoggerTrait, CurlClientTrait, RouterTrait, SessionTrait, MustacheRendererTrait, DebugContainerTrait;
        LoggerTrait::getLogger insteadof CurlClientTrait, SessionTrait;
    }

    /**
     * @param Http\Request $request
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurlClient();
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
        $redirectUrl = (new \EnterRepository\User())->getRedirectUrlByHttpRequest(
            $request,
            $referer ?: $router->getUrlByRoute(new Routing\User\Index())
        );

        // ид региона
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        // запрос региона
        $regionQuery = new Query\Region\GetItemById($regionId);
        $curl->prepare($regionQuery);

        $curl->execute();

        // регион
        $region = (new \EnterRepository\Region())->getObjectByQuery($regionQuery);

        // запрос дерева категорий для меню
        $categoryListQuery = new Query\Product\Category\GetTreeList($region->id, 3);
        $curl->prepare($categoryListQuery);

        // запрос меню
        $mainMenuQuery = new Query\MainMenu\GetItem();
        $curl->prepare($mainMenuQuery);

        $curl->execute();

        // меню
        $mainMenu = (new Repository\MainMenu())->getObjectByQuery($mainMenuQuery, $categoryListQuery);

        // запрос для получения страницы
        $pageRequest = new Repository\Page\User\Login\Request();
        $pageRequest->region = $region;
        $pageRequest->mainMenu = $mainMenu;
        $pageRequest->redirectUrl = $redirectUrl;
        $pageRequest->authFormErrors = array_map(function(\EnterModel\Message $message) { return $message->name; }, $messageRepository->getObjectListByHttpSession('authForm.error', $session));
        $pageRequest->registerFormErrors = array_map(function(\EnterModel\Message $message) { return $message->name; }, $messageRepository->getObjectListByHttpSession('registerForm.error', $session));
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