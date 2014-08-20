<?php

namespace EnterRepository;

use Enter\Http;
use Enter\Curl\Query;
use EnterAggregator\ConfigTrait;
use EnterModel as Model;

class Cart {
    use ConfigTrait;

    /**
     * @param Http\Request $request
     * @return Model\Cart\Product|null
     */
    public function getProductObjectByHttpRequest(Http\Request $request) {
        $cartProduct = null;

        $productItem = [
            'id'       => null,
            'quantity' => null,
        ];
        if (!empty($request->query['product']['id'])) {
            $productItem = array_merge($productItem, $request->query['product']);
        } else if (!empty($request->data['product']['id'])) {
            $productItem = array_merge($productItem, $request->data['product']);
        }

        if ($productItem['id']) {
            $cartProduct = new Model\Cart\Product();
            $cartProduct->id = (string)$productItem['id'];
            $cartProduct->quantity = (int)$productItem['quantity'];
        }

        return $cartProduct;
    }

    /**
     * @param Http\Request $request
     * @return Model\Cart\Product|null
     */
    public function getProductListByHttpRequest(Http\Request $request) {
        $cartProducts = [];

        foreach ((array)$request->data['product'] as $productItem) {
            $productItem = array_merge([
                'id'       => null,
                'quantity' => null,
            ], (array)$productItem);

            if ($productItem['id']) {
                $cartProduct = new Model\Cart\Product();
                $cartProduct->id = (string)$productItem['id'];
                $cartProduct->quantity = (int)$productItem['quantity'];

                $cartProducts[] = $cartProduct;
            }
        }

        return $cartProducts;
    }

    /**
     * @param Http\Request $request
     * @return Model\Cart\Product[]
     */
    public function getProductObjectListByHttpRequest(Http\Request $request) {
        $products = [];
        foreach ((array)$request->query['products'] as $product) {
            if (!isset($product['id'])) continue;

            $cartProduct = new Model\Cart\Product();
            $cartProduct->id = (string)$product['id'];
            $cartProduct->quantity = isset($product['quantity']) ? (int)$product['quantity'] : 0;

            $products[] = $cartProduct;
        }

        return $products;
    }

    /**
     * @param Http\Session $session
     * @return Model\Cart
     */
    public function getObjectByHttpSession(Http\Session $session) {
        $cart = new Model\Cart();

        $cartData = array_merge([
            'product' => [],
        ], (array)$session->get('cart'));

        // импорт старой корзины
        $oldCartData = array_merge([
            'productList' => [],
        ], (array)$session->get('userCart'));
        foreach ($oldCartData['productList'] as $productId => $productQuantity) {
            if (!isset($cartData['product'][$productId])) {
                $cartData['product'][$productId] = [
                    'id'       => $productId,
                    'quantity' => $productQuantity,
                ];
            }
        }

        foreach ($cartData['product'] as $productItem) {
            $cartProduct = new Model\Cart\Product();
            $cartProduct->id = (string)$productItem['id'];
            $cartProduct->quantity = (int)$productItem['quantity'];

            $cart->product[$cartProduct->id] = $cartProduct;
        }

        return $cart;
    }

    /**
     * @param Http\Session $session
     * @param Model\Cart $cart
     */
    public function saveObjectToHttpSession(Http\Session $session, Model\Cart $cart) {
        // TODO: купоны, ...

        // сохранение в старой корзине
        $oldCartData = [
            'productList' => [],
        ];

        foreach ($cart->product as $cartProduct) {
            $oldCartData['productList'][$cartProduct->id] = $cartProduct->quantity;
        }
        $session->set('userCart', $oldCartData);

        // новая корзина
        $cartData = [
            'product' => [],
        ];

        foreach ($cart->product as $cartProduct) {
            $cartData['product'][$cartProduct->id] = [
                'id'       => $cartProduct->id,
                'quantity' => $cartProduct->quantity,
            ];
        }
        $session->set('cart', $cartData);
    }

    /**
     * @param Query $query
     * @return Model\Cart
     */
    public function getObjectByQuery(Query $query) {
        $cart = null;

        $item = $query->getResult();
        $cart = new Model\Cart($item);

        return $cart;
    }

    /**
     * @param Model\Cart $cart
     * @param Model\Cart\Product $cartProduct
     */
    public function setProductForObject(Model\Cart $cart, Model\Cart\Product $cartProduct) {
        if ($cartProduct->quantity <= 0) {
            if (isset($cart->product[$cartProduct->id])) unset($cart->product[$cartProduct->id]);
        } else {
            $cart->product[$cartProduct->id] = $cartProduct;
        }
    }

    /**
     * @param $id
     * @param Model\Cart $cart
     * @return Model\Cart\Product|null
     */
    public function getProductById($id, Model\Cart $cart) {
        $return = null;

        foreach ($cart->product as $cartProduct) {
            if ($cartProduct->id === $id) {
                $return = $cartProduct;

                break;
            }
        }

        return $return;
    }
}