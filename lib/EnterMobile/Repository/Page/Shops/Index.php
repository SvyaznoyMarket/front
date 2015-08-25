<?php

namespace EnterMobile\Repository\Page\Shops;

use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Partial;
use EnterMobile\Model\Page\Shops\Index as Page;
use EnterAggregator\CurlTrait;
use EnterMobile\ConfigTrait;


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

        $page->dataModule = 'shops.index';




        // шаблоны mustache
        // ...

        (new Repository\Template())->setListForPage($page, [
//            [
//                'id'       => 'tpl-product-slider',
//                'name'     => 'partial/product-slider/mainPage',
//                'partials' => [
//                    'partial/cart/flat_button',
//                ],
//            ],
//            [
//                'id'       => 'tpl-product-slider-viewed',
//                'name'     => 'partial/product-slider/viewed'
//            ]
        ]);

        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}