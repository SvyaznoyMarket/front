<?php

namespace EnterSite\Repository\Page\User;

use EnterSite\ConfigTrait;
use EnterSite\LoggerTrait;
use EnterSite\RouterTrait;
use EnterSite\ViewHelperTrait;
use EnterSite\Routing;
use EnterSite\Repository;
use EnterSite\Model;
use EnterSite\Model\Partial;
use EnterSite\Model\Page\User\Login as Page;

class Login {
    use ConfigTrait, LoggerTrait, RouterTrait, ViewHelperTrait {
        ConfigTrait::getConfig insteadof LoggerTrait, RouterTrait, ViewHelperTrait;
    }

    /**
     * @param Page $page
     * @param Login\Request $request
     */
    public function buildObjectByRequest(Page $page, Login\Request $request) {
        (new Repository\Page\DefaultLayout)->buildObjectByRequest($page, $request);

        $config = $this->getConfig();
        $router = $this->getRouter();
        $viewHelper = $this->getViewHelper();

        $templateDir = $config->mustacheRenderer->templateDir;

        $page->dataModule = 'user.login';

        $page->title = 'Авторизация';

        $page->content->redirectUrl = $request->redirectUrl;

        $page->content->authForm = new Model\Form\User\AuthForm();
        $page->content->authForm->url = $router->getUrlByRoute(new Routing\User\Auth());

        $page->content->registerForm = new Model\Form\User\RegisterForm();
        $page->content->registerForm->url = $router->getUrlByRoute(new Routing\User\Register());

        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}