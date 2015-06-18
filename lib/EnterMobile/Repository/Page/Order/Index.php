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
    public function buildObjectByRequest(Page $page, Index\Request $request, $orderFormUserData) {
        (new Repository\Page\DefaultPage)->buildObjectByRequest($page, $request);

        $router = $this->getRouter();
        $templateHelper = $this->getTemplateHelper();

        // заголовок
        $page->title = 'Оформление заказа - Получатель - Enter';

        $page->dataModule = 'order';

        $page->content->form->url = $router->getUrlByRoute(new Routing\Order\SetUser());
        $page->content->form->errorDataValue = $templateHelper->json($request->formErrors);

        if (!empty($userData['firstName'])) {
            $page->content->form->firstName = $userData['firstName'];
        } else if ($request->user->firstName) {
            $page->content->form->firstName = $request->user->firstName;
        }

        if (!empty($userData['phone'])) {
            $page->content->form->phone = $userData['phone'];
        } else if ($request->user->phone) {
            $page->content->form->phone = $request->user->phone;
        }

        if (!empty($userData['email'])) {
            $page->content->form->email = $userData['email'];
        } else if ($request->user->email) {
            $page->content->form->email = $request->user->email;
        }

        if (11 === mb_strlen($page->content->form->phone) && (0 === strpos($page->content->form->phone, '8'))) {
            $page->content->form->phone = preg_replace('/^8/', '+7', $page->content->form->phone);
        }

        $page->content->isUserAuthenticated = (bool)$request->user;
        $page->content->authUrl = $router->getUrlByRoute(
            new Routing\User\Login(),
            ['redirect_to' => $router->getUrlByRoute(new Routing\Order\Index())]
        );
    }
}