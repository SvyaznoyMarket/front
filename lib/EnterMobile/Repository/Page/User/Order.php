<?php

namespace EnterMobile\Repository\Page\User;

use EnterAggregator\CurlTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterMobile\ConfigTrait;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Partial;
use EnterMobile\Model\Page\User\Order as Page;


class Order {
    use LoggerTrait,
        TemplateHelperTrait,
        RouterTrait,
        CurlTrait,
        ConfigTrait;

    /**
     * @param Page $page
     * @param Index\Request $request
     */
    public function buildObjectByRequest(Page $page, Order\Request $request) {
        (new Repository\Page\DefaultPage)->buildObjectByRequest($page, $request);

        $templateHelper = $this->getTemplateHelper();

        $page->dataModule = 'user.order';
        $page->title = 'Заказ';

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

        // заказы
        $page->content->order = $request->order;

        // шаблоны mustache
        // ...

        (new Repository\Template())->setListForPage($page, [

        ]);

        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}