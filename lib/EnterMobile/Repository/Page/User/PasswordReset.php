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
use EnterMobile\Model\Page\User\ResetPassword as Page;

class PasswordReset {
    use ConfigTrait, LoggerTrait, RouterTrait, TemplateHelperTrait;

    /**
     * @param Page $page
     * @param Login\Request $request
     */
    public function buildObjectByRequest(Page $page, PasswordReset\Request $request) {
        (new Repository\Page\DefaultPage)->buildObjectByRequest($page, $request);

        $config = $this->getConfig();
        $router = $this->getRouter();

        $page->dataModule = 'user.resetpassword';

        $page->title = 'Поменять пароль';

        $page->content->redirectUrl = $request->redirectUrl;
        $page->content->resetPasswordForm = new Model\Form\User\ResetPasswordForm();
        $page->content->resetPasswordForm->url = $router->getUrlByRoute(new Routing\User\ChangePassword());
        $page->content->resetPasswordForm->errors = (bool)$request->formErrors ? $request->formErrors : false;

        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}