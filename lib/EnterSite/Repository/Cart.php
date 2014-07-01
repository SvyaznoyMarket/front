<?php

namespace EnterSite\Repository;

use Enter\Http;
use Enter\Curl\Query;
use EnterSite\ConfigTrait;
use EnterSite\Model;

class Cart {
    use ConfigTrait;

    /**
     * @param Http\Request $request
     * @return \EnterModel\Cart\Product|null
     */
    public function getProductObjectByHttpRequest(Http\Request $request) {
        $cartProduct = null;

        $productData = [
            'id'       => null,
            'quantity' => null,
        ];
        if (!empty($request->query['product']['id'])) {
            $productData = array_merge($productData, $request->query['product']);
        } else if (!empty($request->data['product']['id'])) {
            $productData = array_merge($productData, $request->data['product']);
        }

        if ($productData['id']) {
            $cartProduct = new \EnterModel\Cart\Product();
            $cartProduct->id = (string)$productData['id'];
            $cartProduct->quantity = (int)$productData['quantity'];
        }

        return $cartProduct;
    }

    /**
     * @param Http\Session $session
     * @return \EnterModel\Cart
     */
    public function getObjectByHttpSession(Http\Session $session) {
        $cart = new \EnterModel\Cart();

        $cartData = array_merge([
            'productList' => [],
        ], (array)$session->get('userCart'));

        foreach ($cartData['productList'] as $productId => $productQuantity) {
            $cartProduct = new \EnterModel\Cart\Product();
            $cartProduct->id = (string)$productId;
            $cartProduct->quantity = (int)$productQuantity;

            $cart->product[$cartProduct->id] = $cartProduct;
        }

        return $cart;
    }

    /**
     * @param Http\Session $session
     * @param \EnterModel\Cart $cart
     */
    public function saveObjectToHttpSession(Http\Session $session, \EnterModel\Cart $cart) {
        // TODO: купоны, ...

        $cartData = [
            'productList' => [],
        ];

        foreach ($cart->product as $cartProduct) {
            $cartData['productList'][$cartProduct->id] = $cartProduct->quantity;
        }

        $session->set('userCart', $cartData);
    }

    /**
     * @param Query $query
     * @return \EnterModel\Cart
     */
    public function getObjectByQuery(Query $query) {
        $cart = null;

        $item = $query->getResult();
        $cart = new \EnterModel\Cart($item);

        return $cart;
    }

    /**
     * @param \EnterModel\Cart $cart
     * @param \EnterModel\Cart\Product $cartProduct
     */
    public function setProductForObject(\EnterModel\Cart $cart, \EnterModel\Cart\Product $cartProduct) {
        if ($cartProduct->quantity <= 0) {
            if (isset($cart->product[$cartProduct->id])) unset($cart->product[$cartProduct->id]);
        } else {
            $cart->product[$cartProduct->id] = $cartProduct;
        }
    }

    /**
     * @param $id
     * @param \EnterModel\Cart $cart
     * @return \EnterModel\Cart\Product|null
     */
    public function getProductById($id, \EnterModel\Cart $cart) {
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