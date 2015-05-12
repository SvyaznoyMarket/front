<?php

namespace EnterMobile\Repository\Page;

use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Partial;
use EnterMobile\Model\Page\Index as Page;

class Index {
    use ConfigTrait, LoggerTrait, RouterTrait, TemplateHelperTrait;

    /**
     * @param Page $page
     * @param Index\Request $request
     */
    public function buildObjectByRequest(Page $page, Index\Request $request) {
        (new Repository\Page\DefaultPage)->buildObjectByRequest($page, $request);

        $config = $this->getConfig();
        $router = $this->getRouter();
        $templateHelper = $this->getTemplateHelper();

        $page->dataModule = 'index';

        $hosts = $config->mediaHosts;
        $host = reset($hosts);

        $promoData = [];
        foreach ($request->promos as $promoModel) {
            $image = null;
            foreach ($promoModel->media->photos as $photo) {
                if (in_array('main', $photo->tags) && !empty($photo->sources[0]->url)) {
                    $image = $photo->sources[0]->url;
                    break;
                }
            }

            if (!$image) {
                $this->getLogger()->push(['type' => 'warn', 'error' => sprintf('Нет картинки у промо #', $promoModel->id), 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['promo']]);
                continue;
            }
            $promoItem = [
                'id'    => $promoModel->id,
                'url'   => $router->getUrlByRoute(new Routing\Promo\Redirect($promoModel->id)),
                'image' => $image,
            ];

            $promoData[] = $promoItem;
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