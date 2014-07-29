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
        (new Repository\Page\DefaultLayout)->buildObjectByRequest($page, $request);

        $config = $this->getConfig();
        $router = $this->getRouter();
        $viewHelper = $this->getTemplateHelper();

        $templateDir = $config->mustacheRenderer->templateDir;

        $page->dataModule = 'index';

        $hosts = $config->mediaHosts;
        $host = reset($hosts);

        $promoData = [];
        foreach ($request->promos as $promoModel) {
            if (!$promoModel->image) {
                $this->getLogger()->push(['type' => 'warn', 'error' => sprintf('Нет картинки у промо #', $promoModel->id), 'action' => __METHOD__, 'tag' => ['promo']]);
                continue;
            }
            $promoItem = [
                'id'    => $promoModel->id,
                'url'   => $router->getUrlByRoute(new Routing\Promo\Redirect($promoModel->id)),
                'image' => $host . $config->promo->urlPaths[1] . $promoModel->image,
            ];

            $promoData[] = $promoItem;
        }
        $page->content->promoDataValue = $viewHelper->json($promoData);

        // ga
        $walkByMenu = function(array $menuElements) use(&$walkByMenu, &$viewHelper) {
            /** @var \EnterModel\MainMenu\Element[] $menuElements */
            foreach ($menuElements as $menuElement) {
                $menuElement->dataGa = $viewHelper->json([
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
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'action' => __METHOD__, 'tag' => ['partner']]);
        }

        // шаблоны mustache
        foreach ([

        ] as $templateItem) {
            try {
                $template = new Model\Page\DefaultLayout\Template();
                $template->id = $templateItem['id'];
                $template->content = file_get_contents($templateDir . '/' . $templateItem['name'] . '.mustache');

                $page->templates[] = $template;
            } catch (\Exception $e) {
                $this->getLogger()->push(['type' => 'error', 'error' => $e, 'action' => __METHOD__, 'tag' => ['template']]);
            }
        }

        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}