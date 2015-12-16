<?php

namespace EnterMobile\Controller\User\Cart;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\AbTestTrait;
use EnterAggregator\LoggerTrait;
use EnterQuery as Query;
use EnterMobile\Model;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model\Page\User\Cart\SetProduct as Page;

class DeleteProduct {
    use ConfigTrait, CurlTrait, SessionTrait, RouterTrait, AbTestTrait, LoggerTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();
        $session = $this->getSession();
        $cartRepository = new \EnterRepository\Cart();

        // корзина из сессии
        $cart = $cartRepository->getObjectByHttpSession($session, $config->cart->sessionKey);

        // товара для корзины
        $cartProduct = $cartRepository->getProductObjectByHttpRequest($request);
        if (!$cartProduct) {
            throw new \Exception('Товар не получен');
        }
        $cartProduct->quantity = 0;

        $previousProduct = $cartRepository->getProductById($cartProduct->id, $cart);

        $responseProduct = [
            (string)$cartProduct->id => [
                'newQuantity' => 0,
                'previousQuantity' => $previousProduct ? $previousProduct->quantity : 0,
            ],
        ];

        // добавление товара в корзину
        $cartRepository->setProductForObject($cart, $cartProduct);

        // регион
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);
        $region = $regionId ? new \EnterModel\Region(['id' => $regionId]) : null;

        $cartProductListQuery = new Query\Product\GetListByIdList([$cartProduct->id], $regionId);
        $cartProductDescriptionListQuery = new Query\Product\GetDescriptionListByIdList([$cartProduct->id]);
        $curl->prepare($cartProductListQuery);
        $curl->prepare($cartProductDescriptionListQuery);

        // запрос пользователя
        $userItemQuery = (new \EnterMobile\Repository\User())->getQueryByHttpRequest($request);
        if ($userItemQuery) {
            $curl->prepare($userItemQuery);
        }

        // запрос корзины
        $cartItemQuery = new Query\Cart\Price\GetItem($cart, $regionId);
        $curl->prepare($cartItemQuery);

        $productsById = [];
        call_user_func(function() use($cart, &$productsById) {
            foreach ($cart->product as $cartProduct) {
                $productsById[$cartProduct->id] = null;
            }
        });

        $productListQuery = null;
        if ($productsById) {
            $productListQuery = new Query\Product\GetListByIdList(array_keys($productsById), $regionId);
            $curl->prepare($productListQuery);
        }

        $descriptionListQuery = null;
        if ($productsById) {
            $descriptionListQuery = new Query\Product\GetDescriptionListByIdList(
                array_keys($productsById),
                [
                    'media'    => true,
                    'category' => true,
                    'label'    => true,
                    'brand'    => true,
                ]
            );
            $curl->prepare($descriptionListQuery);
        }

        $curl->execute();

        // корзина из ядра
        $cartRepository->updateObjectByQuery($cart, $cartItemQuery);

        // сохранение корзины в сессию
        $cartRepository->saveObjectToHttpSession($session, $cart, $config->cart->sessionKey);

        // удалить разбиение заказа
        $session->remove($config->order->splitSessionKey);

        // товар
        $product = (new \EnterRepository\Product())->getObjectByQueryList([$cartProductListQuery], [$cartProductDescriptionListQuery]);
        if (!$product) {
            $product = new \EnterModel\Product();
            $product->id = $cartProduct->id;

            throw new \Exception(sprintf('Товар #%s не найден', $cartProduct->id));
        }

        // пользователь
        $user = (new \EnterMobile\Repository\User())->getObjectByQuery($userItemQuery);

        // серверная корзина
        if ($user && $this->getAbTest()->isCoreCartEnabled()) {
            $removeQuery = new Query\Cart\DeleteProductItem($product->ui, $user->ui);
            $curl->prepare($removeQuery);

            $curl->execute(null, 1);
        }

        // товары
        if ($productListQuery) {
            $productsById = (new \EnterRepository\Product())->getIndexedObjectListByQueryList([$productListQuery], [$descriptionListQuery]);
        }

        // если корзина пустая
        if (!count($cart)) {
            return new Http\JsonResponse([
                'redirect' => $this->getRouter()->getUrlByRoute(new Routing\Cart\Index()),
            ]);
        }

        $page = new Page();
        // кнопка купить
        if ($widget = (new Repository\Partial\Cart\ProductButton())->getObject($product, $cartProduct)) {
            $page->widgets['.' . $widget->widgetId] = $widget;
        }
        // спиннер
        if ($widget = (new Repository\Partial\Cart\ProductSpinner())->getObject($product, $cartProduct)) {
            $page->widgets['.' . $widget->widgetId] = $widget;
        }
        // пользователь, корзина
        if ($widget = (new Repository\Partial\UserBlock())->getObject($cart, $user)) {
            $page->widgets['.' . $widget->widgetId] = $widget;
        }
        if ($widget = (new Repository\Partial\Cart())->getObject($cart, array_values($productsById), $region)) {
            $page->widgets['.' . $widget->widgetId] = $widget;
        }

        // response
        $response = new Http\JsonResponse([
            'result' => $page, // TODO: вынести на уровень JsonPage.result
            'products' => $responseProduct,
        ]);

        return $response;
    }
}