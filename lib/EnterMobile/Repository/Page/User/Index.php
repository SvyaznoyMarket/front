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

        $page->dataModule = 'user';

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
            'name'       => implode(' ', [$userModel->firstName, $userModel->lastName]),
            'firstName'  => $userModel->firstName,
            'lastName'   => $userModel->lastName,
            'middleName' => $userModel->middleName,
            'birthday'   => $userModel->birthday,
            'phone'      => (11 === strlen($userModel->phone)) ? preg_replace('/(\d{1,3})(\d{1,3})(\d{1,2})(\d{1,2})/i', '+7 ($1) $2-$3-$4', substr($userModel->phone, 1)) : $userModel->phone,
            'homePhone'  => $userModel->homePhone,
            'sex'        =>
                null === $userModel->sex
                ? false
                : (1 == $userModel->sex ? 'муж' : 'жен')
            ,
            'email'      => $userModel->email,
            'occupation' => $userModel->occupation,
        ];
        $page->content->isIndexActive = true;

        // шаблоны mustache
        // ...

        (new Repository\Template())->setListForPage($page, []);

        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}