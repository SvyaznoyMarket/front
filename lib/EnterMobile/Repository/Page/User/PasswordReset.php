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
    use ConfigTrait,
        LoggerTrait,
        RouterTrait,
        TemplateHelperTrait;

    /**
     * @param Page $page
     * @param Login\Request $request
     */
    public function buildObjectByRequest(Page $page, PasswordReset\Request $request) {
        (new Repository\Page\DefaultPage)->buildObjectByRequest($page, $request);

        $config = $this->getConfig();
        $router = $this->getRouter();
        $templateHelper = $this->getTemplateHelper();

        $page->title = 'Поменять пароль';

        // ga
        $walkByMenu = function(array $menuElements) use(&$walkByMenu, &$templateHelper) {
            /** @var \EnterModel\MainMenu\Element[] $menuElements */
            foreach ($menuElements as $menuElement) {
                $menuElement->dataGa = $templateHelper->json([
                    'm_main_category' => ['send', 'event', 'm_main_category', $menuElement->name],
                ]);
                /*
                if ((bool)$menuElement->children) {
                    $walkByMenu($menuElement->children);
                }
                */
            }
        };
        $walkByMenu($request->mainMenu->elements);

        $page->content->resetPasswordForm = new Model\Form\User\ResetPasswordForm();
        $page->content->resetPasswordForm->url = $router->getUrlByRoute(new Routing\User\ChangePassword());
        $page->content->resetPasswordForm->errors = (bool)$request->formErrors ? $request->formErrors : false;

        $page->content->messages = (new Repository\Partial\Message())->getList($request->messages);

        $page->dataModule = 'user.profile';

        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}