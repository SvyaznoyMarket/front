<?php

namespace EnterMobile\Repository\Page\User;

use EnterAggregator\CurlTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterAggregator\PriceHelperTrait;
use EnterMobile\ConfigTrait;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Partial;
use EnterMobile\Model\Page\User\Index as Page;


class Index {
    use LoggerTrait,
        TemplateHelperTrait,
        RouterTrait,
        CurlTrait,
        ConfigTrait,
        PriceHelperTrait;

    /**
     * @param Page $page
     * @param Index\Request $request
     */
    public function buildObjectByRequest(Page $page, Index\Request $request) {
        (new Repository\Page\User\DefaultPage)->buildObjectByRequest($page, $request);

        $templateHelper = $this->getTemplateHelper();

        $page->title = 'Личный кабинет';

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

        $userModel = $request->user;
        $page->content->user = [
            'firstName'  => $userModel->firstName,
            'lastName'   => $userModel->lastName,
            'middleName' => $userModel->middleName,
            'birthday'   => $userModel->birthday,
            'phone'      => $userModel->phone,
            'homePhone'  => $userModel->homePhone,
            'sex'        =>
                null === $userModel->sex
                ? false
                : (1 == $userModel->sex ? 'муж' : 'жен')
            ,
            'email'      => $userModel->email,
            'occupation' => $userModel->occupation,
        ];

        // шаблоны mustache
        // ...

        (new Repository\Template())->setListForPage($page, []);

        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}