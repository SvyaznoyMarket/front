<?php

namespace EnterMobileApplication\Controller\Cart;

use Enter\Http;
use EnterMobileApplication\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\AbTestTrait;
use EnterAggregator\LoggerTrait;
use EnterMobileApplication\Controller;
use EnterQuery as Query;

class SetProductList {
    use ConfigTrait, CurlTrait, SessionTrait, AbTestTrait, LoggerTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $curl = $this->getCurl();
        $cartRepository = new \EnterRepository\Cart();

        $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request); // FIXME
        $userAuthToken = is_scalar($request->query['token']) ? (string)$request->query['token'] : null;

        if (!$regionId) {
            throw new \Exception('Не задан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
        }

        if (!$userAuthToken) {
            throw new \Exception('Не задан параметр token', Http\Response::STATUS_BAD_REQUEST);
        }

        if (0 === strpos($userAuthToken, 'anonymous-')) {
            throw new \Exception('Параметр token содержит идентификатор анонимного пользователя (данный метод предназначен для работы только с токенами аутентифицированных пользователей)', Http\Response::STATUS_BAD_REQUEST);
        }

        $userItemQuery = new Query\User\GetItemByToken($userAuthToken);
        $curl->prepare($userItemQuery);

        $regionQuery = new Query\Region\GetItemById($regionId);
        $curl->prepare($regionQuery);
        $curl->execute();

        $user = (new \EnterRepository\User())->getObjectByQuery($userItemQuery);
        $region = (new \EnterRepository\Region())->getObjectByQuery($regionQuery);

        if (!$user || !$user->ui) {
            throw new \Exception('Не удалось получить ui пользователя', Http\Response::STATUS_BAD_REQUEST);
        }

        // Добавление товаров
        call_user_func(function() use(&$user, &$region, &$curl, &$cartRepository, &$request) {
            $cartProducts = $cartRepository->getProductObjectListByHttpRequest($request);
            if (!$cartProducts) {
                throw new \Exception('Товары не получены', Http\Response::STATUS_BAD_REQUEST);
            }

            $productsById = [];
            foreach ($cartProducts as $cartProduct) {
                $productsById[$cartProduct->id] = null;
            }

            if ($productsById) {
                $productListQuery = new Query\Product\GetListByIdList(array_keys($productsById), $region->id, ['related' => false]);
                $productDescriptionListQuery = new Query\Product\GetDescriptionListByIdList(array_keys($productsById));
                $curl->prepare($productListQuery);
                $curl->prepare($productDescriptionListQuery);
                $curl->execute();

                $productsById = (new \EnterRepository\Product())->getIndexedObjectListByQueryList([$productListQuery], [$productDescriptionListQuery]);
            }

            foreach ($cartProducts as $cartProduct) {
                if (!isset($productsById[$cartProduct->id])) {
                    continue;
                }

                $curl->prepare(
                    new Query\Cart\SetQuantityForProductItem(
                        $productsById[$cartProduct->id]->ui,
                        null === $cartProduct->quantity ? '+1' : $cartProduct->quantity,
                        $user->ui
                    )
                );
            }

            $curl->execute();
        });

        // Получение корзины
        call_user_func(function() use(&$cart, &$user, &$region, &$curl, &$cartRepository) {
            $cartItemQuery = new Query\Cart\GetItem($user->ui);
            $curl->prepare($cartItemQuery);
            $curl->execute();

            $cart = $cartRepository->getObjectByQuery($cartItemQuery);

            $cartProductListQuery = null;
            $cartProductDescriptionListQuery = null;
            if ($cart->product) {
                $productUis = array_map(function (\EnterModel\Cart\Product $product) { return $product->ui; }, $cart->product);
                $cartProductListQuery = new \EnterQuery\Product\GetListByUiList($productUis, $region->id, ['related' => false]);
                $cartProductDescriptionListQuery = new Query\Product\GetDescriptionListByUiList($productUis);
                $curl->prepare($cartProductListQuery);
                $curl->prepare($cartProductDescriptionListQuery);
            }

            $curl->execute();

            $cartRepository->updateObjectByQuery($cart, null, $cartProductListQuery, $cartProductDescriptionListQuery);

            $cartPriceItemQuery = new \EnterQuery\Cart\Price\GetItem($cart, $region->id);
            $curl->prepare($cartPriceItemQuery);
            $curl->execute();

            // TODO: избавиться от данного (повторного) вызова когда в v2/cart/get-price будет добавлена поддержка передачи ui товаров
            $cartRepository->updateObjectByQuery($cart, $cartPriceItemQuery);
        });

        return new Http\JsonResponse(['cart' => (new \EnterMobileApplication\Repository\Cart())->getResponseArray($cart)]);
    }
}
