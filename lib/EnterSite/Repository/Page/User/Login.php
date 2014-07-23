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
        $page->content->authForm->username = $request->httpRequest->data['username'];
        $page->content->authForm->errors = (bool)$request->httpRequest->data['authForm_error'] ? $request->httpRequest->data['authForm_error'] : false;

        $page->content->registerForm = new Model\Form\User\RegisterForm();
        $page->content->registerForm->url = $router->getUrlByRoute(new Routing\User\Register());
        $page->content->registerForm->name = $request->httpRequest->data['name'];
        $page->content->registerForm->email = $request->httpRequest->data['email'];
        $page->content->registerForm->phone = $request->httpRequest->data['phone'];
        $page->content->registerForm->errors = (bool)$request->httpRequest->data['registerForm_error'] ? $request->httpRequest->data['registerForm_error'] : false;

        if ((bool)$request->httpRequest->data['registerForm_error']) {
            $page->content->registerForm->isHidden = false;
            $page->content->authForm->isHidden = true;
        } else {
            $page->content->registerForm->isHidden = true;
            $page->content->authForm->isHidden = false;
        }

        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}