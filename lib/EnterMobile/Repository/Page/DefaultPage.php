<?php

namespace EnterMobile\Repository\Page;

use EnterAggregator\AbTestTrait;
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
    use RequestIdTrait, ConfigTrait, RouterTrait, LoggerTrait, TemplateHelperTrait, AbTestTrait;

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
            if (file_exists($config->dir . '/version')) {
                $page->dataVersion = file_get_contents($config->dir . '/version');
            }
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['repository']]);
        }

        if (!$page->dataVersion) {
            $page->dataVersion = date('ymd');
        }

        $page->dataModule = 'default';

        $castedRequest = (array)$request;
        $productId = (isset($castedRequest['product'])) ? $castedRequest['product']->id : null;

        // body[data-value]
        $page->dataConfig = $templateHelper->json([
            'requestId' => $this->getRequestId(),
            'debug'     => $config->debugLevel,
            'env'       => $config->environment,
            'cookie'    => [
                'domain'   => $config->session->cookieDomain,
                'lifetime' => $config->session->cookieLifetime,
            ],
            'credit'    => [
                'cookieName' => $config->credit->cookieName,
            ],
            'siteVersionSwitcher' => [
                'cookieName' => $config->siteVersionSwitcher->cookieName,
                'cookieLifetime' => $config->siteVersionSwitcher->cookieLifetime,
            ],
            'kladr' => [
                'token'  => $config->kladr->token,
                'key'    => $config->kladr->key,
                'limit'  => $config->kladr->limit,
                'city'   =>
                    $request->region
                    ? [
                        'id'   => $request->region->kladrId,
                        'name' => $request->region->name,
                    ]
                    : null,
            ],
            'region' =>
                $request->region
                ? [
                    'name' => $request->region->name,
                ]
                : null
            ,
            'productId' => $productId
        ]);

        $page->dataUser = $templateHelper->json($request->user ? [
            'id' => $request->user->id,
        ] : null);

        call_user_func(function() use($page, $request, $templateHelper) {
            $dataCart = ['products' => []];

            foreach ($request->cart->product as $cartProduct) {
                $dataCart['products'][] = [
                    'id' => $cartProduct->id,
                    'name' => $cartProduct->product ? $cartProduct->product->name : null,
                    'price' => $cartProduct->price,
                    'quantity' => $cartProduct->quantity,
                ];
            }

            $page->dataCart = $templateHelper->json($dataCart ? $dataCart : null);
        });

        // Виджеты, выполняемые при загрузке страницы
        call_user_func(function() use($page, $request, $templateHelper) {
            $dataWidget = [];

            $userBlock = (new Repository\Partial\UserBlock())->getObject($request->cart, $request->user);
            $dataWidget['.' . $userBlock->widgetId] = $userBlock;

            foreach ($request->cart->product as $cartProduct) {
                $product = $cartProduct->product ?: new \EnterModel\Product(['id' => $cartProduct->id]);

                if ($widget = (new Repository\Partial\ProductCard\CartButtonBlock())->getObject($product, $cartProduct)) {
                    $dataWidget['.' . $widget->widgetId] = $widget;
                }

                if ($widget = (new Repository\Partial\Cart\ProductButton())->getObject($product, $cartProduct)) {
                    $dataWidget['.' . $widget->widgetId] = $widget;
                }

                // кнопка купить для родительского товара
                if ($cartProduct->parentId && $widget = (new Repository\Partial\Cart\ProductButton())->getObject(
                        new \EnterModel\Product(['id' => $cartProduct->parentId]),
                        new \EnterModel\Cart\Product(['id' => $cartProduct->parentId, 'quantity' => 1])
                    )) {
                    $dataWidget['.' . $widget->widgetId] = $widget;
                }

                if ($widget = (new Repository\Partial\Cart\ProductSpinner())->getObject(
                    $product,
                    $cartProduct,
                    false
                )) {
                    $dataWidget['.' . $widget->widgetId] = $widget;
                }
            }

            $page->dataWidget = $templateHelper->json($dataWidget ? $dataWidget : null);
        });


        $page->googleAnalytics = false;
        if ($config->googleAnalytics->enabled) {
            $page->googleAnalytics = new Page\GoogleAnalytics();
            $page->googleAnalytics->regionName = $request->region ? $request->region->name : null;
            $page->googleAnalytics->userAuth = $request->user ? '1' : '0';
            $page->googleAnalytics->hostname = $config->hostname;

            foreach ($this->getAbTest()->getObjectList() as $test) {
                if ($test->gaSlotNumber) {
                    $abTest = new Page\GoogleAnalytics\AbTest();
                    $abTest->gaSlotNumber = $test->gaSlotNumber;
                    $abTest->gaSlotScope = $test->gaSlotScope;
                    $abTest->chosenToken = $test->token . '_' . $test->chosenItem->token;

                    $page->googleAnalytics->abTests[] = $abTest;
                }
            }
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

        $serviceElements = [];
        if ($page->mainMenu) {
            if ($request->user) {
                foreach ($request->mainMenu->serviceElements as $key => $serviceElement) {
                    if ($key != 'user') {
                        $serviceElements[] = $serviceElement;
                        continue;
                    }

                    $request->mainMenu->serviceElements[$key]['name'] = $request->user->firstName.' '.$request->user->lastName;
                    $request->mainMenu->serviceElements[$key]['iconClass'] = ($request->user->isEnterprizeMember) ? 'nav-icon--lk-ep' : 'nav-icon--lk-log';
                    $serviceElements[] = $request->mainMenu->serviceElements[$key];
                }
            } else {
                foreach ($request->mainMenu->serviceElements as $key => $serviceElement) {
                    $serviceElements[] = $request->mainMenu->serviceElements[$key];
                }
            }

            $page->mainMenu->serviceElements = $serviceElements;
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
            [
                'id'   => 'tpl-modalWindow',
                'name' => 'partial/modalWindow',
            ],
        ]);
    }
}