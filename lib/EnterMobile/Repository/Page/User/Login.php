<?php

namespace EnterMobile\Repository\Page\User;

use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Partial;
use EnterMobile\Model\Page\User\Login as Page;

class Login {
    use ConfigTrait, LoggerTrait, RouterTrait, TemplateHelperTrait;

    /**
     * @param Page $page
     * @param Login\Request $request
     */
    public function buildObjectByRequest(Page $page, Login\Request $request) {
        (new Repository\Page\DefaultPage)->buildObjectByRequest($page, $request);

        $config = $this->getConfig();
        $router = $this->getRouter();

        $page->dataModule = 'user.login';

        $page->title = 'Авторизация';

        $page->content->redirectUrl = $request->redirectUrl;

        $page->content->messages = (new Repository\Partial\Message())->getList($request->messages);

        $page->content->authForm = new Model\Form\User\AuthForm($request->authFormFields);
        $page->content->authForm->url = $router->getUrlByRoute(new Routing\User\Auth());
        $page->content->authForm->errors = (bool)$request->authFormErrors ? $request->authFormErrors : false;

        $page->content->resetForm = new Model\Form\User\ResetForm($request->resetFormFields);
        $page->content->resetForm->url = $router->getUrlByRoute(new Routing\User\Reset());
        $page->content->resetForm->errors = (bool)$request->resetFormErrors ? $request->resetFormErrors : false;

        $page->content->registerForm = new Model\Form\User\RegisterForm($request->registerFormFields);
        $page->content->registerForm->url = $router->getUrlByRoute(new Routing\User\Register());
        $page->content->registerForm->errors = (bool)$request->registerFormErrors ? $request->registerFormErrors : false;

        if ((bool)$request->registerFormErrors) {
            $page->content->registerForm->isHidden = false;
            $page->content->authForm->isHidden = true;
            $page->content->resetForm->isHidden = true;
        } else if ((bool)$request->resetFormErrors) {
            $page->content->resetForm->isHidden = false;
            $page->content->registerForm->isHidden = true;
            $page->content->authForm->isHidden = true;
        } else {
            $page->content->authForm->isHidden = false;
            $page->content->registerForm->isHidden = true;
            $page->content->resetForm->isHidden = true;
        }

        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}