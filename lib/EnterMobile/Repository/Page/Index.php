<?php

namespace EnterMobile\Repository\Page;

use EnterAggregator\LoggerTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Partial;
use EnterMobile\Model\Page\Index as Page;

class Index {
    use LoggerTrait, TemplateHelperTrait;

    /**
     * @param Page $page
     * @param Index\Request $request
     */
    public function buildObjectByRequest(Page $page, Index\Request $request) {
        (new Repository\Page\DefaultPage)->buildObjectByRequest($page, $request);

        $templateHelper = $this->getTemplateHelper();

        $page->dataModule = 'index';

        $promoData = [];
        foreach ($request->promos as $promoModel) {
            $source = $promoModel->getPhotoMediaSource('mobile', 'original');

            if (!$source || !$source->url) {
                $this->getLogger()->push(['type' => 'warn', 'error' => sprintf('Нет картинки у промо #', $promoModel->ui), 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['promo']]);
                continue;
            }

            $promoData[] = [
                'ui'    => $promoModel->ui,
                'url'   => $promoModel->target->url,
                'image' => $source->url,
            ];
        }
        $page->content->promoDataValue = $templateHelper->json($promoData);

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

        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}