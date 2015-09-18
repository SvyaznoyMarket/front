<?php

namespace EnterMobile\Repository\Page\User;

use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Partial;
use EnterMobile\Model\Page\User\EditProfile as Page;

class EditProfile {
    use ConfigTrait,
        LoggerTrait,
        RouterTrait,
        SessionTrait,
        TemplateHelperTrait;

    /**
     * @param Page $page
     * @param Login\Request $request
     */
    public function buildObjectByRequest(Page $page, EditProfile\Request $request) {
        (new Repository\Page\User\DefaultPage)->buildObjectByRequest($page, $request);

        $config = $this->getConfig();
        $router = $this->getRouter();
        $templateHelper = $this->getTemplateHelper();

        $page->title = 'Редактирование профиля';
        $page->dataModule = 'user.profile';

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

        $userInfo = (array)$request->user;
        $userInfo['birthdayHelper'] = $userInfo['birthday'];

        $userInfo['birthday'] = date('d.m.Y', strtotime($userInfo['birthday']));

        if ($userInfo['isEnterprizeMember']) {
            $userInfo['disabledFields']['mobile'] = true;
            $userInfo['disabledFields']['email'] = true;
        }

        $editProfileForm = new Model\Form\User\EditProfileForm($userInfo);

        $page->content->editProfileForm = $editProfileForm;
        $page->content->editProfileForm->url = $router->getUrlByRoute(new Routing\User\Edit\Save());
        $page->content->editProfileForm->errors = (bool)$request->formErrors ? $request->formErrors : false;
        $page->content->editProfileForm->selectedSex = [
            'male' => ($editProfileForm->sex) ? true : false,
            'female' => ($editProfileForm->sex == 2) ? true : false,
        ];

        $page->content->messages = (new Repository\Partial\Message())->getList($request->messages);

        $page->dataModule = 'user.profile';

        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}