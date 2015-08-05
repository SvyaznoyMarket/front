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
use EnterMobile\Model\Page\User\EnterprizeList as Page;


class EnterprizeList {
    use LoggerTrait,
        TemplateHelperTrait,
        RouterTrait,
        CurlTrait,
        ConfigTrait,
        PriceHelperTrait;

    /**
     * @param Page $page
     * @param Enterprize\Request $request
     */
    public function buildObjectByRequest(Page $page, Enterprize\Request $request) {
        (new Repository\Page\User\DefaultPage)->buildObjectByRequest($page, $request);

        $templateHelper = $this->getTemplateHelper();

        $page->title = 'Enterprize';

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
        if (!empty($request->coupons)) {
            $coupons = [];
            foreach ($request->coupons as $couponObject) {
                $coupons[] = [
                    'id'=> $couponObject->id,
                    'backgroundUrl' => $couponObject->backgroundImageUrl,
                    'imageUrl' => $couponObject->productSegment->imageUrl,
                    'discountAmount' => $couponObject->discount->value,
                    'discountUnit' => $couponObject->discount->unit,
                    'category' => $couponObject->productSegment->name,
                    'description' => $couponObject->productSegment->description,
                    'minOrderSum' => $this->getPriceHelper()->format($couponObject->minOrderSum)
                ];
            }

            $page->content->coupons = $coupons;
        }



        // шаблоны mustache
        // ...

        (new Repository\Template())->setListForPage($page, [

        ]);

        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}