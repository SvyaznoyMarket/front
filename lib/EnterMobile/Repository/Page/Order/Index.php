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

        // заголовок
        $page->title = 'Оформление заказа - Получатель - Enter';

        $page->dataModule = 'order-new';

        $page->content->form->url = $router->getUrlByRoute(new Routing\Order\SetUser(), ['shopId' => $request->shopId]);
        $page->content->form->errorDataValue = $templateHelper->json($request->formErrors);

        if ($request->user && $request->user->firstName) {
            $page->content->form->firstName = $request->user->firstName;
        } else if (!empty($request->userData['firstName'])) {
            $page->content->form->firstName = $request->userData['firstName'];
        }

        if ($request->user && $request->user->phone) {
            $page->content->form->phone = $request->user->phone;
        } else if (!empty($request->userData['phone'])) {
            $page->content->form->phone = $request->userData['phone'];
        }

        if ($request->user && $request->user->email) {
            $page->content->form->email = $request->user->email;
        } else if (!empty($request->userData['email'])) {
            $page->content->form->email = $request->userData['email'];
        }

        if (11 === mb_strlen($page->content->form->phone) && (0 === strpos($page->content->form->phone, '8'))) {
            $page->content->form->phone = preg_replace('/^8/', '+7', $page->content->form->phone);
        }

        $page->content->isUserAuthenticated = (bool)$request->user;
        $page->content->authUrl = $router->getUrlByRoute(
            new Routing\User\Login(),
            ['redirect_to' => $router->getUrlByRoute(new Routing\Order\Index())]
        );

        foreach ($request->formErrors as $errorModel) {
            if (!isset($errorModel['message'])) continue;

            $page->content->errors[] = [
                'message' => $errorModel['message'],
            ];
        }

        $page->content->hasMnogoRu = isset($request->bonusCardsByType[\EnterModel\BonusCard::TYPE_MNOGORU]);

        $page->steps = [
            ['name' => 'Получатель', 'isPassive' => true, 'isActive' => true],
            ['name' => 'Самовывоз и доставка', 'isPassive' => false, 'isActive' => false],
            ['name' => 'Оплата', 'isPassive' => false, 'isActive' => false],
        ];
    }
}