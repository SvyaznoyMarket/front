<?php

namespace EnterMobile\Repository\Partial;

use EnterMobile\ConfigTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Partial;

class Partner {
    use ConfigTrait, TemplateHelperTrait;

    /**
     * Данные по умолчанию
     *
     * @param \EnterMobile\Repository\Page\DefaultPage\Request $request
     * @return array
     */
    public function getDefaultList(Repository\Page\DefaultPage\Request $request) {
        $config = $this->getConfig()->partner->service;
        $templateHelper = $this->getTemplateHelper();

        $dataAction = 'default';

        $partners = [];

        // actionpay
        if ($config->actionpay->enabled) {
            $partner = new Partial\Partner();
            $partner->id = 'actionpay';
            $partner->dataAction = $dataAction;

            $partners[] = $partner;
        }

        // google retargeting
        if ($config->googleRetargeting->enabled) {
            $partner = new Partial\Partner();
            $partner->id = 'google-retargeting';
            $partner->dataAction = $dataAction;
            $partner->dataValue = $templateHelper->json($this->getGoogleRetargetingDataValue());

            $partners[] = $partner;
        }

        // cityads
        if ($config->cityads->enabled) {
            $partner = new Partial\Partner();
            $partner->id = 'cityads';
            $partner->dataAction = $dataAction;
            $partner->dataValue = $templateHelper->json([]);

            $partners[] = $partner;
        }

        return $partners;
    }

    /**
     * Данные для главной страницы
     *
     * @param Repository\Page\Index\Request $request
     * @return array
     */
    public function getListForIndex(Repository\Page\Index\Request $request) {
        $config = $this->getConfig()->partner->service;
        $templateHelper = $this->getTemplateHelper();

        $dataAction = 'index';

        $partners = [];

        // actionpay
        if ($config->actionpay->enabled) {
            $partner = new Partial\Partner();
            $partner->id = 'actionpay';
            $partner->dataAction = $dataAction;
            $partner->dataValue = $templateHelper->json([
                'pageType' => 1,
            ]);

            $partners[] = $partner;
        }

        // google retargeting
        if ($config->googleRetargeting->enabled) {
            $partner = new Partial\Partner();
            $partner->id = 'google-retargeting';
            $partner->dataAction = $dataAction;

            $dataValue = $this->getGoogleRetargetingDataValue();
            $dataValue['tagParams'] = array_merge($dataValue['tagParams'], ['pagetype' => 'homepage']);
            $partner->dataValue = $templateHelper->json($dataValue);

            $partners[] = $partner;
        }

        // cityads
        if ($config->cityads->enabled) {
            $partner = new Partial\Partner();
            $partner->id = 'cityads';
            $partner->dataAction = $dataAction;
            $partner->dataValue = $templateHelper->json([]);

            $partners[] = $partner;
        }

        return $partners;
    }

    /**
     * Данные для каталога товаров
     *
     * @param Repository\Page\ProductCatalog\RootCategory\Request $request
     * @return array
     */
    public function getListForProductCatalog(Repository\Page\ProductCatalog\RootCategory\Request $request) {
        $config = $this->getConfig()->partner->service;
        $templateHelper = $this->getTemplateHelper();

        $category = $request->category;
        $products = $request instanceof Repository\Page\ProductCatalog\ChildCategory\Request ? $request->products : [];
        $dataAction = 'product.catalog';

        $partners = [];

        // actionpay
        if ($config->actionpay->enabled) {
            $partner = new Partial\Partner();
            $partner->id = 'actionpay';
            $partner->dataAction = $dataAction;
            $partner->dataValue = $templateHelper->json([
                'pageType'         => 3,
                'currentCategory'  => $category ? ['id' => $category->id, 'name' => $category->name] : null,
                'parentCategories' => ($category && $category->parent)
                    ? [
                        ['id' => $category->parent->id, 'name' => $category->parent->name],
                    ]
                    : [],
            ]);

            $partners[] = $partner;
        }

        // criteo
        if ($config->criteo->enabled) {
            if ((bool)$products) {

                $partner = new Partial\Partner();
                $partner->id = 'criteo';
                $partner->dataAction = $dataAction;
                $partner->dataValue = $templateHelper->json(array_merge($this->getCriteoDataValue(), [
                    [
                        'event' => 'viewList',
                        'item'  => array_values(array_map(function(\EnterModel\Product $product) { return $product->id; }, $products)),
                    ],
                ]));

                $partners[] = $partner;
            }
        }

        // sociomantic
        if ($config->sociomantic->enabled) {
            $partner = new Partial\Partner();
            $partner->id = 'sociomantic';
            $partner->dataAction = $dataAction;
            $partner->dataValue = $templateHelper->json([
                'category' => $category ? array_map(function(\EnterModel\Product\Category $category) { return $category->name; }, array_merge($category->ascendants, [$category])) : [],
            ]);
            $partners[] = $partner;
        }

        // google retargeting
        if ($config->googleRetargeting->enabled) {
            $partner = new Partial\Partner();
            $partner->id = 'google-retargeting';
            $partner->dataAction = $dataAction;

            $dataValue = $this->getGoogleRetargetingDataValue();
            $dataValue['tagParams'] = array_merge($dataValue['tagParams'], ['pagetype' => 'category', 'pcat' => $category ? $category->token : null]);
            $partner->dataValue = $templateHelper->json($dataValue);

            $partners[] = $partner;
        }

        // cityads
        if ($config->cityads->enabled) {
            $partner = new Partial\Partner();
            $partner->id = 'cityads';
            $partner->dataAction = $dataAction;
            $partner->dataValue = $templateHelper->json([]);

            $partners[] = $partner;
        }

        return $partners;
    }

    /**
     * Данные для результатов поиска
     *
     * @param Repository\Page\Search\Request $request
     * @return array
     */
    public function getListForSearch(Repository\Page\Search\Request $request) {
        $config = $this->getConfig()->partner->service;
        $templateHelper = $this->getTemplateHelper();

        $products = $request->products;
        $dataAction = 'search';

        $partners = [];

        // criteo
        if ($config->criteo->enabled) {
            if ((bool)$products) {

                $partner = new Partial\Partner();
                $partner->id = 'criteo';
                $partner->dataAction = $dataAction;
                $partner->dataValue = $templateHelper->json(array_merge($this->getCriteoDataValue(), [
                    [
                        'event' => 'viewList',
                        'item'  => array_values(array_map(function(\EnterModel\Product $product) { return $product->id; }, $products)),
                    ],
                ]));

                $partners[] = $partner;
            }
        }

        // google retargeting
        if ($config->googleRetargeting->enabled) {
            $partner = new Partial\Partner();
            $partner->id = 'google-retargeting';
            $partner->dataAction = $dataAction;

            $dataValue = $this->getGoogleRetargetingDataValue();
            $partner->dataValue = $templateHelper->json($dataValue);

            $partners[] = $partner;
        }

        // cityads
        if ($config->cityads->enabled) {
            $partner = new Partial\Partner();
            $partner->id = 'cityads';
            $partner->dataAction = $dataAction;
            $partner->dataValue = $templateHelper->json([]);

            $partners[] = $partner;
        }

        return $partners;
    }

    /**
     * Данные для карточки товара
     *
     * @param Repository\Page\ProductCard\Request $request
     * @return array
     */
    public function getListForProductCard(Repository\Page\ProductCard\Request $request) {
        $config = $this->getConfig()->partner->service;
        $templateHelper = $this->getTemplateHelper();

        $product = $request->product;
        $category = $request->product->category;
        $dataAction = 'product.card';

        $partners = [];

        // actionpay
        if ($config->actionpay->enabled) {
            $partner = new Partial\Partner();
            $partner->id = 'actionpay';
            $partner->dataAction = $dataAction;
            $partner->dataValue = $templateHelper->json([
                'pageType'         => 2,
                'currentProduct'   => ['id' => $product->id, 'name' => $product->name, 'price' => $product->price],
                'currentCategory'  => $category ? ['id' => $category->id, 'name' => $category->name] : null,
                'parentCategories' => ($category && $category->parent)
                    ? [
                        ['id' => $category->parent->id, 'name' => $category->parent->name],
                    ]
                    : [],
            ]);

            $partners[] = $partner;
        }

        // criteo
        if ($config->criteo->enabled) {
            $partner = new Partial\Partner();
            $partner->id = 'criteo';
            $partner->dataAction = $dataAction;
            $partner->dataValue = $templateHelper->json(array_merge($this->getCriteoDataValue(), [
                [
                    'event'   => 'viewItem',
                    'account' => $product->id,
                ],
            ]));

            $partners[] = $partner;
        }

        // sociomantic
        if ($config->sociomantic->enabled) {
            $categoryNames = $category ? array_map(function(\EnterModel\Product\Category $category) { return $category->name; }, array_merge($category->ascendants, [$category])) : [];
            $description = $product->tagline ?: ($product->description ?: $product->name);
            if (mb_strlen($description) > 90) {
                $description = mb_substr($description, 0, 90) . '...';
            }
            $photo = isset($product->media->photos[0]) ? $product->media->photos[0] : null;

            $partner = new Partial\Partner();
            $partner->id = 'sociomantic';
            $partner->dataAction = $dataAction;
            $partner->dataValue = $templateHelper->json([
                'product'  => [
                    'amount'      => $product->oldPrice ?: $product->price,
                    'brand'       => $product->brand ? $product->brand->name : null,
                    'category'    => $categoryNames,
                    'currency'    => 'RUB',
                    'description' => $description,
                    'fn'          => $product->name,
                    'identifier'  => $product->article . '_' . $request->region->id,
                    'photo'       => $photo ? ((string)(new Routing\Product\Media\GetPhoto($photo, 'product_500'))) : null,
                    'price' => $product->price,
                    'url'   => $request->httpRequest->getSchemeAndHttpHost() . $product->link,
                    'valid' => $product->isBuyable ? 0 : time(),
                ],
                'category' => $categoryNames,
            ]);

            $partners[] = $partner;
        }

        // google retargeting
        if ($config->googleRetargeting->enabled) {
            $partner = new Partial\Partner();
            $partner->id = 'google-retargeting';
            $partner->dataAction = $dataAction;

            $dataValue = $this->getGoogleRetargetingDataValue();
            $dataValue['tagParams'] = array_merge($dataValue['tagParams'], [
                'pagetype' => 'product',
                'prodid'   => $product->id,
                'pname'    => $product->name,
                'pcat'     => ($category) ? $category->token : '',
                'pvalue'   => $product->price,
            ]);
            $partner->dataValue = $templateHelper->json($dataValue);

            $partners[] = $partner;
        }

        // cityads
        if ($config->cityads->enabled) {
            $partner = new Partial\Partner();
            $partner->id = 'cityads';
            $partner->dataAction = $dataAction;
            $partner->dataValue = $templateHelper->json([
                'page'    => 'product',
                'product' => ['id' => $product->id],
            ]);

            $partners[] = $partner;
        }

        return $partners;
    }

    /**
     * Данные для корзины
     *
     * @param Repository\Page\Cart\Index\Request $request
     * @return array
     */
    public function getListForCart(Repository\Page\Cart\Index\Request $request) {
        $config = $this->getConfig()->partner->service;
        $templateHelper = $this->getTemplateHelper();

        $cart = $request->cart;
        $dataAction = 'cart';

        $partners = [];

        // actionpay
        if ($config->actionpay->enabled) {
            $partner = new Partial\Partner();
            $partner->id = 'actionpay';
            $partner->dataAction = $dataAction;
            $partner->dataValue = $templateHelper->json([
                'pageType' => 4,
            ]);

            $partners[] = $partner;
        }

        // criteo
        if ($config->criteo->enabled) {
            $partner = new Partial\Partner();
            $partner->id = 'criteo';
            $partner->dataAction = $dataAction;
            $partner->dataValue = $templateHelper->json(array_merge($this->getCriteoDataValue(), [
                [
                    'event' => 'viewBasket',
                    'item'  => array_values(array_map(function(\EnterModel\Cart\Product $product) { return [
                        'id'       => $product->id,
                        'price'    => $product->price,
                        'quantity' => $product->quantity,
                    ]; }, $cart->product)),
                ],
            ]));

            $partners[] = $partner;
        }

        // sociomantic
        if ($config->sociomantic->enabled) {
            $partner = new Partial\Partner();
            $partner->id = 'sociomantic';
            $partner->dataAction = $dataAction;
            $partner->dataValue = $templateHelper->json([
                'cartProduct' => array_values(array_map(function(\EnterModel\Cart\Product $cartProduct) use (&$request) {
                    return [
                        'amount'     => $cartProduct->price * $cartProduct->quantity,
                        'currency'   => 'RUB',
                        'identifier' => $cartProduct->product ? ($cartProduct->product->article . '_' . $request->region->id) : null,
                        'quantity'   => $cartProduct->quantity,
                    ];
                }, $cart->product))
            ]);

            $partners[] = $partner;
        }

        // google retargeting
        if ($config->googleRetargeting->enabled) {
            $partner = new Partial\Partner();
            $partner->id = 'google-retargeting';
            $partner->dataAction = $dataAction;

            $dataValue = $this->getGoogleRetargetingDataValue();
            $tagParams = ['pagetype' => 'cart', 'cartvalue' => $cart->sum, 'prodid' => [], 'pname' => [], 'pcat' => []];
            foreach ($cart->product as $cartProduct) {
                if (!$cartProduct->product) continue;

                $tagParams['prodid'][] = $cartProduct->product->id;
                $tagParams['pname'][] = $cartProduct->product->name;
                $tagParams['pcat'][] = $cartProduct->product->category ? $cartProduct->product->category->token : '';
            }
            $dataValue['tagParams'] = array_merge($dataValue['tagParams'], $tagParams);
            $partner->dataValue = $templateHelper->json($dataValue);

            $partners[] = $partner;
        }

        // cityads
        if ($config->cityads->enabled) {
            $productIds = [];
            $productQuantities = [];
            foreach ($cart->product as $cartProduct) {
                $productIds[] = $cartProduct->id;
                $productQuantities[] = $cartProduct->quantity;
            }

            $partner = new Partial\Partner();
            $partner->id = 'cityads';
            $partner->dataAction = $dataAction;
            $partner->dataValue = $templateHelper->json([
                'page'            => 'cart',
                'productId'       => implode(',', $productIds),
                'productQuantity' => implode(',', $productQuantities),
            ]);

            $partners[] = $partner;
        }

        return $partners;
    }


    /**
     * @return array
     */
    private function getCriteoDataValue() {
        $config = $this->getConfig()->partner->service->criteo;

        return [
            [
                'event'   => 'setAccount',
                'account' => $config->account,
            ],
            [
                'event' => 'setCustomerId',
                'id'    => '{userId}',
            ],
            [
                'event' => 'setSiteType',
                'type'  => 'm',
            ],
        ];
    }

    /**
     * @return array
     */
    private function getGoogleRetargetingDataValue() {
        return [
            'tagParams' => [
                'pagetype' => 'default',
            ],
        ];
    }
}