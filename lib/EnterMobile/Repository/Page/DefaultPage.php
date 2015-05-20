<?php

namespace EnterMobile\Repository\Page;

use EnterAggregator\RequestIdTrait;
use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Page\DefaultPage as Page;

class DefaultPage {
    use RequestIdTrait, ConfigTrait, RouterTrait, LoggerTrait, TemplateHelperTrait;

    /**
     * @param Page $page
     * @param Repository\Page\DefaultPage\Request $request
     */
    public function buildObjectByRequest(Page $page, DefaultPage\Request $request) {
        $config = $this->getConfig();
        $templateHelper = $this->getTemplateHelper();
        $router = $this->getRouter();

        // стили
        $page->styles[] = '/css/global.css';

        // заголовок
        $page->title = 'Enter - все товары для жизни по интернет ценам!';

        $page->fullHost = $this->getConfig()->fullHost;
        $page->dataDebug = $config->debugLevel ? 'true' : '';
        try {
            $page->dataVersion = file_get_contents($config->dir . '/version') ?: date('ymd');
        } catch (\Exception $e) {
            $page->dataVersion = date('ymd');

            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['repository']]);
        }
        $page->dataModule = 'default';

        // body[data-value]
        $page->dataConfig = $templateHelper->json([
            'requestId' => $this->getRequestId(),
            'debug'     => $config->debugLevel,
            'env'       => $config->environment,
            'cookie'    => [
                'domain'   => $config->session->cookieDomain,
                'lifetime' => $config->session->cookieLifetime,
            ],
            'user'      => [
                'infoUrl'    => $router->getUrlByRoute(new Routing\User\Get()),
            ],
            'credit'    => [
                'cookieName' => $config->credit->cookieName,
            ],
            'siteVersionSwitcher' => [
                'cookieName' => $config->siteVersionSwitcher->cookieName,
                'cookieLifetime' => $config->siteVersionSwitcher->cookieLifetime,
            ],
            'wikimart'  =>
                $config->wikimart->enabled
                ? [
                    'url'               => $config->wikimart->jsUrl,
                    'productUrlPattern' => $request->httpRequest ? ($request->httpRequest->getSchemeAndHttpHost() . '/wm-product/%GOOD_ID%') : null,
                ]
                : null
            ,
        ]);

        $page->googleAnalytics = false;
        if ($config->googleAnalytics->enabled) {
            $page->googleAnalytics = new Page\GoogleAnalytics();
            $page->googleAnalytics->id = $config->googleAnalytics->id;
        }

        $page->googleTagManager = false;
        if ($config->googleTagManager->enabled) {
            $page->googleTagManager = new Page\GoogleTagManager();
            $page->googleTagManager->id = $config->googleTagManager->id;
        }

        $page->yandexMetrika = false;
        if ($config->yandexMetrika->enabled) {
            $page->yandexMetrika = new Page\YandexMetrika();
            $page->yandexMetrika->id = $config->yandexMetrika->id;
        }

        $page->mailRu = false;
        if ($config->mailRu->enabled) {
            $page->mailRu = new Page\MailRu();
            $page->mailRu->id = $config->mailRu->id;
            $page->mailRu->productIds = '[]';
            $page->mailRu->pageType = 'other';
            $page->mailRu->price = '';
        }

        // регион
        $page->regionBlock->regionName = $request->region ? $request->region->name : null;
        $page->regionBlock->setUrl = $router->getUrlByRoute(new Routing\Region\SetByName());
        $page->regionBlock->autocompleteUrl = $router->getUrlByRoute(new Routing\Region\Autocomplete());
        foreach ([ // TODO: вынести в конфиг
            ['id' => '14974', 'name' => 'Москва'],
            ['id' => '108136', 'name' => 'Санкт-Петербург'],
        ] as $regionItem) {
            $region = new Page\RegionBlock\Region();
            $region->name = $regionItem['name'];
            $region->url = $router->getUrlByRoute(new Routing\Region\SetById($regionItem['id']));
            $region->dataGa = $templateHelper->json([
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
        $walkByMenu = function(array $menuElements) use(&$walkByMenu, &$templateHelper) {
            /** @var \EnterModel\MainMenu\Element[] $menuElements */
            foreach ($menuElements as $menuElement) {
                $menuElement->dataGa = $templateHelper->json([
                    'm_sidebar_category_click' => ['send', 'event', 'm_sidebar_category_click', $menuElement->name],
                ]);
                if ((bool)$menuElement->children) {
                    $walkByMenu($menuElement->children);
                }
            }
        };
        if ($request->mainMenu) {
            $walkByMenu($request->mainMenu->elements);
        }

        // partner
        try {
            $page->partners = (new Repository\Partial\Partner())->getDefaultList($request);
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['partner']]);
        }

        // шаблоны mustache
        (new Repository\Template())->setListForPage($page, [
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
            [
                'id'   => 'tpl-cart-slot-form',
                'name' => 'partial/cart/slot/form',
            ],
            [
                'id'   => 'tpl-cart-slot-form-result',
                'name' => 'partial/cart/slot/form/result',
            ],
        ]);
    }
}