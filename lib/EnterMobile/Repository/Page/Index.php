<?php

namespace EnterMobile\Repository\Page;

use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterAggregator\AbTestTrait;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Partial;
use EnterMobile\Model\Page\Index as Page;


class Index {
    use ConfigTrait,
        LoggerTrait,
        TemplateHelperTrait,
        RouterTrait,
        CurlTrait,
        AbTestTrait
    ;

    /**
     * @param Page $page
     * @param Index\Request $request
     */
    public function buildObjectByRequest(Page $page, Index\Request $request) {
        (new Repository\Page\DefaultPage)->buildObjectByRequest($page, $request);

        $config = $this->getConfig();
        $router = $this->getRouter();
        $templateHelper = $this->getTemplateHelper();

        $productSliderRepository = new Repository\Partial\ProductSlider();
        $mediaRepository = new \EnterRepository\Media();

        $page->dataModule = 'index';
        $page->bodyClass = 'body-main';

        $promoData = [];
        foreach ($request->promos as $promoModel) {
            $source = $promoModel->getPhotoMediaSource('mobile', 'original');

            if (!$source || !$source->url) {
                $this->getLogger()->push(['type' => 'warn', 'error' => sprintf('Нет картинки у промо #', $promoModel->ui), 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['promo']]);
                continue;
            }

            if ($promoModel->target instanceof \EnterModel\Promo\Target\Content) {
                $targetUrl = $this->getRouter()->getUrlByRoute(new \EnterMobile\Routing\Content($promoModel->target->contentId));
            } else if ($promoModel->target instanceof \EnterModel\Promo\Target\Slice && $promoModel->target->sliceId && $promoModel->target->categoryToken) {
                $targetUrl = $this->getRouter()->getUrlByRoute(new \EnterMobile\Routing\ProductSlice\GetCategory($promoModel->target->sliceId, $promoModel->target->categoryToken));
            } else {
                $targetUrl = $promoModel->target->url;
            }

            $promoData[] = [
                'ui'    => $promoModel->ui,
                'url'   => $targetUrl,
                'image' => $source->url,
            ];
        }
        $page->content->promoDataValue = $templateHelper->json($promoData);
        $page->content->promos = $promoData;

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
        $recommendListUrl = $router->getUrlByRoute(new Routing\Index\Recommendations());
        $page->content->popularSlider = $productSliderRepository->getObject('popularSlider', $recommendListUrl);
        $page->content->personalSlider = $productSliderRepository->getObject('personalSlider', $recommendListUrl);
        $page->content->viewedSlider = $productSliderRepository->getObject('viewedSlider', $recommendListUrl);
        
        call_user_func(function() use(&$page, &$request, &$mediaRepository) {
            foreach ($request->popularBrands as $brand) {
                if (!isset($lastGroup) || count($lastGroup) == 2) {
                    unset($lastGroup);
                    $lastGroup = [];
                    $page->content->popularBrands[] = &$lastGroup;
                }
                
                $lastGroup[] = [
                    'name' => $brand->name,
                    'url' => $brand->url,
                    'imageUrl' => $mediaRepository->getSourceObjectByList($brand->media->photos, 'main', '70x35')->url,
                ];
            }
        });

        $page->headerTitle = false;

        // расположение главного меню
        $page->content->mainMenuOnBottom = ('top' === $this->getAbTest()->getObjectByToken('msite_main_categories')->chosenItem->token) ? true : false;

        // шаблоны mustache
        // ...

        (new Repository\Template())->setListForPage($page, [
            [
                'id'       => 'tpl-product-slider',
                'name'     => 'partial/product-slider/mainPage',
                'partials' => [
                    'partial/cart/flat_button',
                ],
            ],
            [
                'id'       => 'tpl-product-slider-viewed',
                'name'     => 'partial/product-slider/viewed'
            ]
        ]);

        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}