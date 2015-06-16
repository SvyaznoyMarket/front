<?php

namespace EnterMobile\Repository\Page\Order;

use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Partial;
use EnterMobile\Model\Page\Order\Index as Page;

class Index {
    use ConfigTrait, LoggerTrait, RouterTrait, TemplateHelperTrait;

    /**
     * @param Page $page
     * @param Index\Request $request
     */
    public function buildObjectByRequest(Page $page, Index\Request $request) {
        (new Repository\Page\DefaultPage)->buildObjectByRequest($page, $request);

        $router = $this->getRouter();
        $templateHelper = $this->getTemplateHelper();

        $userModel = $request->user;

        // заголовок
        $page->title = 'Оформление заказа - Получатель - Enter';

        $page->dataModule = 'order';

        $page->content->form->url = $router->getUrlByRoute(new Routing\Order\SetUser());
        $page->content->form->errorDataValue = $templateHelper->json($request->formErrors);
        if ($userModel) {
            $page->content->form->email = $userModel->email;
            $page->content->form->phone = $userModel->phone;
            $page->content->form->firstName = $userModel->firstName;
        }

        if (11 === mb_strlen($page->content->form->phone) && (0 === strpos($page->content->form->phone, '8'))) {
            $page->content->form->phone = preg_replace('/^8/', '+7', $page->content->form->phone);
        }

        $page->content->isUserAuthenticated = (bool)$userModel->id;
        $page->content->authUrl = $router->getUrlByRoute(
            new Routing\User\Login(),
            ['redirect_to' => $router->getUrlByRoute(new Routing\Order\Index())]
        );

        $page->steps = [
            ['name' => 'Получатель', 'isPassive' => true, 'isActive' => true],
            ['name' => 'Самовывоз и доставка', 'isPassive' => false, 'isActive' => false],
            ['name' => 'Оплата', 'isPassive' => false, 'isActive' => false],
        ];
    }
}