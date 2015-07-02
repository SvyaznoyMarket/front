<?php

namespace EnterMobileApplication\Controller\Cart;

use Enter\Http;
use EnterAggregator\CurlTrait;
use EnterAggregator\LoggerTrait;
use EnterMobileApplication\ConfigTrait;
use EnterAggregator\SessionTrait;
use EnterMobileApplication\Controller;
use EnterQuery as Query;

class DeleteProductList {
    use ConfigTrait, LoggerTrait, CurlTrait, SessionTrait;

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

        $ids = (array)$request->data['ids'];
        $uis = (array)$request->data['uis'];

        if (!$ids && !$uis) {
            throw new \Exception('Не переданы параметры ids и uis', Http\Response::STATUS_BAD_REQUEST);
        }

        // Удаление товаров
        call_user_func(function() use(&$ids, &$uis, &$quantity, &$user, &$region, &$curl) {
            if ($ids) {
                $productListQuery = new \EnterQuery\Product\GetListByIdList($ids, $region->id);
                $curl->prepare($productListQuery);
                $curl->execute();

                $uis = array_merge($uis, array_map(function(\EnterModel\Product $product) { return $product->ui; }, (new \EnterRepository\Product())->getIndexedObjectListByQueryList([$productListQuery])));
            }

            if ($uis) {
                foreach ($uis as $ui) {
                    $curl->prepare(new Query\Cart\DeleteProductItem($ui, $user->ui));
                }

                $curl->execute();
            }
        });

        // Получение корзины
        call_user_func(function() use(&$cart, &$user, &$region, &$curl, &$cartRepository) {
            $cartItemQuery = new Query\Cart\GetItem($user->ui);
            $curl->prepare($cartItemQuery);
            $curl->execute();

            $cart = $cartRepository->getObjectByQuery($cartItemQuery);

            $cartProductListQuery = null;
            if ($cart->product) {
                $cartProductListQuery = new \EnterQuery\Product\GetListByUiList(array_map(function (\EnterModel\Cart\Product $product) { return $product->ui; }, $cart->product), $region->id);
                $curl->prepare($cartProductListQuery);
            }

            $curl->execute();

            $cartRepository->updateObjectByQuery($cart, null, $cartProductListQuery);

            $cartPriceItemQuery = new \EnterQuery\Cart\Price\GetItem($cart, $region->id);
            $curl->prepare($cartPriceItemQuery);
            $curl->execute();

            // TODO: избавиться от данного (повторного) вызова когда в v2/cart/get-price будет добавлена поддержка передачи ui товаров
            $cartRepository->updateObjectByQuery($cart, $cartPriceItemQuery);
        });

        return new Http\JsonResponse(['cart' => (new \EnterMobileApplication\Repository\Cart())->getResponseArray($cart)]);
    }
}
