<?php

namespace EnterMobile\Repository\Page\Order\Payment;

use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterAggregator\PriceHelperTrait;
use EnterAggregator\DateHelperTrait;
use EnterAggregator\TranslateHelperTrait;
use EnterMobile\TemplateRepositoryTrait;
use EnterMobile\Repository;
use EnterMobile\Model\Page\Order\Payment\Fail as Page;

class Fail {
    use ConfigTrait, LoggerTrait, RouterTrait, TemplateHelperTrait, PriceHelperTrait, DateHelperTrait, TranslateHelperTrait, TemplateRepositoryTrait;

    /**
     * @param Page $page
     * @param \EnterMobile\Repository\Page\Order\Payment\Fail\Request $request
     */
    public function buildObjectByRequest(Page $page, \EnterMobile\Repository\Page\Order\Payment\Fail\Request $request) {
        (new Repository\Page\DefaultPage)->buildObjectByRequest($page, $request);

        $page->title = 'Оформление заказа - Завершение - Enter';
        $page->steps = [
            ['name' => 'Получатель', 'isPassive' => false, 'isActive' => false],
            ['name' => 'Самовывоз и доставка', 'isPassive' => false, 'isActive' => false],
            ['name' => 'Оплата', 'isPassive' => false, 'isActive' => true],
        ];
    }
}