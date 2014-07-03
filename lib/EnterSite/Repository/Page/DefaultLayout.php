<?php

namespace EnterSite\Repository\Page;

use EnterSite\ConfigTrait;
use EnterSite\LoggerTrait;
use EnterSite\RouterTrait;
use EnterSite\ViewHelperTrait;
use EnterSite\Routing;
use EnterSite\Repository;
use EnterSite\Model;
use EnterSite\Model\Page\DefaultLayout as Page;

class DefaultLayout {
    use ConfigTrait, RouterTrait, LoggerTrait, ViewHelperTrait {
        ConfigTrait::getConfig insteadof LoggerTrait;
    }

    /**
     * @param Page $page
     * @param DefaultLayout\Request $request
     */
    public function buildObjectByRequest(Page $page, DefaultLayout\Request $request) {
        $config = $this->getConfig();
        $viewHelper = $this->getViewHelper();
        $router = $this->getRouter();

        $templateDir = $config->mustacheRenderer->templateDir;

        // стили
        $page->styles[] = '/css/global.css';

        // заголовок
        $page->title = 'Enter - все товары для жизни по интернет ценам!';

        $page->dataDebug = $config->debugLevel ? 'true' : '';
        $page->dataVersion = date('ymd');
        $page->dataModule = 'default';

        // body[data-value]
        $page->dataConfig = $viewHelper->json([
            'requestId' => $config->requestId,
            'debug'     => $config->debugLevel,
            'env'       => $config->environment,
            'cookie'     => [
                'domain'   => $config->session->cookieDomain,
                'lifetime' => $config->session->cookieLifetime,
            ],
            'user'      => [
                'infoUrl'    => $router->getUrlByRoute(new Routing\User\Get()),
            ],
            'credit'     => [
                'cookieName' => $config->credit->cookieName,
            ],
        ]);

        $page->googleAnalytics = false;
        if ($config->googleAnalitics->enabled) {
            $page->googleAnalytics = new Page\GoogleAnalytics();
            $page->googleAnalytics->id = $config->googleAnalitics->id;
        }

        // регион
        $page->regionBlock->regionName = $request->region->name;
        $page->regionBlock->setUrl = $router->getUrlByRoute(new Routing\Region\SetByName());
        $page->regionBlock->autocompleteUrl = $router->getUrlByRoute(new Routing\Region\Autocomplete());
        foreach ([ // TODO: вынести в конфиг
            ['id' => '14974', 'name' => 'Москва'],
            ['id' => '108136', 'name' => 'Санкт-Петербург'],
        ] as $regionItem) {
            $region = new Page\RegionBlock\Region();
            $region->name = $regionItem['name'];
            $region->url = $router->getUrlByRoute(new Routing\Region\SetById($regionItem['id']));
            $region->dataGa = $viewHelper->json([
                'm_city_changed' => ['send', 'event', 'm_city_changed', $regionItem['name']],
            ]);

            $page->regionBlock->regions[] = $region;
        }

        // главное меню
        $page->mainMenu = $request->mainMenu;

        // пользователь
        $page->userBlock->isUserAuthorized = false;
        $page->userBlock->userLink->url = $router->getUrlByRoute(new Routing\User\Login());
        $page->userBlock->cart->url = $router->getUrlByRoute(new Routing\Cart\Index());

        // ga
        $walkByMenu = function(array $menuElements) use(&$walkByMenu, &$viewHelper) {
            /** @var \EnterSite\Model\MainMenu\Element[] $menuElements */
            foreach ($menuElements as $menuElement) {
                $menuElement->dataGa = $viewHelper->json([
                    'm_sidebar_category_click' => ['send', 'event', 'm_sidebar_category_click', $menuElement->name],
                ]);
                if ((bool)$menuElement->children) {
                    $walkByMenu($menuElement->children);
                }
            }
        };
        $walkByMenu($request->mainMenu->elements);

        // шаблоны mustache
        foreach ([
            [
                'id'   => 'tpl-product-buyButton',
                'name' => 'partial/cart/button',
            ],
            [
                'id'   => 'tpl-product-buySpinner',
                'name' => 'partial/cart/spinner',
            ],
            [
                'id'   => 'tpl-user',
                'name' => 'partial/user',
            ],
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
    }
}