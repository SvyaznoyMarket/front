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
use EnterMobile\Model\Page\User\Index as Page;


class Index {
    use LoggerTrait,
        TemplateHelperTrait,
        RouterTrait,
        CurlTrait,
        ConfigTrait;

    /**
     * @param Page $page
     * @param Index\Request $request
     */
    public function buildObjectByRequest(Page $page, Index\Request $request) {
        (new Repository\Page\DefaultPage)->buildObjectByRequest($page, $request);

        $config = $this->getConfig();
        $router = $this->getRouter();
        $templateHelper = $this->getTemplateHelper();

        $page->dataModule = 'user.index';

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

        // partner
        try {
            $page->partners = (new Repository\Partial\Partner())->getListForIndex($request);
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['partner']]);
        }

        // шаблоны mustache
        // ...

        (new Repository\Template())->setListForPage($page, [
//            [
//                'id'       => 'tpl-product-slider',
//                'name'     => 'partial/product-slider/mainPage',
//                'partials' => [
//                    'partial/cart/flat_button',
//                ],
//            ]
        ]);

        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}