<?php

namespace EnterMobile\Repository\Page;

use EnterAggregator\AbTestTrait;
use EnterAggregator\RequestIdTrait;
use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterAggregator\TranslateHelperTrait;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Page\DefaultPage as Page;

class DefaultPage {
    use RequestIdTrait, ConfigTrait, RouterTrait, LoggerTrait, TemplateHelperTrait, TranslateHelperTrait, AbTestTrait;

    /**
     * @param Page $page
     * @param Repository\Page\DefaultPage\Request $request
     */
    public function buildObjectByRequest(Page $page, DefaultPage\Request $request) {
        $config = $this->getConfig();
        $templateHelper = $this->getTemplateHelper();
        $translateHelper = $this->getTranslateHelper();
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
            if ($request->user) {
                $page->googleAnalytics->user = new Page\GoogleAnalytics\User();
                $page->googleAnalytics->user->id = $request->user->id;
            }

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

        // баннер доставки
        if (false && $request->region && $request->region->pointCount) {
            $page->deliveryBanner = new Page\DeliveryBanner();
            $page->deliveryBanner->regionName =
                $request->region->inflectedName
                ? $request->region->inflectedName->locativus
                : $request->region->name;

            $deliveryMessage = $this->getDeliveryBannerText($request->region);
            $page->deliveryBanner->beginning = $deliveryMessage['beginning'];
            $page->deliveryBanner->condition = $deliveryMessage['condition'];

            if (isset($deliveryMessage['showCount'])) {
                $page->deliveryBanner->showCount = $deliveryMessage['showCount'];
            }

            $page->deliveryBanner->pointCountMessage = sprintf('&nbsp;%s&nbsp;%s&nbsp;', $request->region->pointCount, $translateHelper->numberChoice($request->region->pointCount, ['точки', 'точек', 'точек']));
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
        $page->userBlock = (new Repository\Partial\UserBlock())->getObject($request->cart, $request->user);

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

        if (isset($request->httpRequest)) {
            $redirectTo = $request->httpRequest->getPathInfo();
        } else {
            $redirectTo = $router->getUrlByRoute(new Routing\Index());
        }

        $serviceElements = [];

        if ($page->mainMenu) {
            if ($request->user) {
                foreach ($request->mainMenu->serviceElements as $key => $serviceElement) {
                    if ($key == 'delivery') {
                        $request->mainMenu->serviceElements[$key]['link'] = $router->getUrlByRoute(
                            new Routing\Shop\Index(),
                            ['redirect_to' => $redirectTo]
                        );
                        $serviceElements[] = $request->mainMenu->serviceElements[$key];
                        continue;
                    }

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

    private function getDeliveryBannerText($region) {
        if (in_array($region->id, [
            14974, // Москва
            108136, // Санкт-Петербург
            83210, // Брянск
            96423, // Владимир
            18074, // Воронеж
            124229, // Казань
            148110, // Калуга
            74562, // Курск
            99, // Липецк
            99958, // Нижний Новгород
            18073, // Тверь
            74358, // Тула
            124232, // Чебоксары
            93746, // Ярославль
            13241, // Белгород
            93747, // Иваново
            13242, // Орел
            83209, // Тамбов
            10374, // Рязань
        ])) {
            return [
                'beginning' => 'Бесплатные доставка домой и в офис и самовывоз из',
                'condition' => 'Для заказов от 1990 <span class="rubl">p</span>',
            ];
        } elseif(in_array($region->id, [
            88434, // Смоленск
            119623, // Ростов-на-Дону
            124201, // Саратов
            124190, // Краснодар
            93751, // Екатеринбург
            124217, // Ставрополь
            93749, // Самара
            143707, // Волгоград
            93752, // Челябинск
            93748, // Уфа
            152595, // Вологда
            124216, // Псков
            124226, // Оренбург
            124230, // Ижевск
            124227, // Пенза
            124231, // Ульяновск
            78637, // Великий Новгород
            124224, // Йошкар-Ола
            124213, // Петрозаводск
            124223, // Киров
            124225, // Саранск
        ])) {
            return [
                'beginning' => 'Бесплатная доставка домой и в офис',
                'condition' => 'Для заказов от 1990 <span class="rubl">p</span>',
                'showCount' => false
            ];
        } elseif (in_array($region->parentId, [
            82, // Москва
            14974, // Москва
            83, // Московская область
            14975, // Санкт-Петербург г
            39, // Санкт-Петербург г
            108136, // Санкт-Петербург
            34, // Ленинградская обл
            73, // Белгородская обл
            74, // Брянская обл
            75, // Владимирская обл
            76, // Воронежская обл
            77, // Ивановская обл
            78, // Калужская обл
            79, // Костромская обл
            80, // Курская обл
            81, // Липецкая обл
            18, // Нижегородская обл
            84, // Орловская обл
            98, // Рязанская обл
            86, // Смоленская обл
            87, // Тамбовская обл
            88, // Тверская обл
            89, // Тульская обл
            90, // Ярославская обл
            27, // Чувашская Республика - Чувашия
            24, // Татарстан Респ
        ])) {
            return [
                'beginning' => 'Бесплатный самовывоз из',
                'condition' => 'Для заказов от 1990 <span class="rubl">p</span>',
            ];

        }
    }
}
