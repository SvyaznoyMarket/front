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
     * @return Model\Cart\Product[]
     */
    public function getProductListByHttpRequest(Http\Request $request) {
        $cartProducts = [];

        foreach ((array)$request->data['product'] as $productItem) {
            $productItem = array_merge([
                'id'         => null,
                'quantity'   => null,
                'parentId'   => null,
            ], (array)$productItem);
            if (!$productItem['id']) continue;

            $cartProduct = new Model\Cart\Product();
            $cartProduct->id = (string)$productItem['id'];
            $cartProduct->quantity = (int)$productItem['quantity'];
            $cartProduct->parentId = (string)$productItem['parentId'];
            $cartProduct->quantitySign = (isset($productItem['quantitySign']) && in_array($productItem['quantitySign'], ['-', '+']))
                ? (string)$productItem['quantitySign']
                : null; // FIXME

            $cartProducts[] = $cartProduct;
        }

        return $cartProducts;
    }

    /**
     * @param Http\Request $request
     * @return Model\Cart\Product[]
     */
    public function getProductObjectListByHttpRequest(Http\Request $request) {
        $productData = (array)(is_array($request->query['products']) ? $request->query['products'] : $request->data['products']);

        $products = [];
        foreach ($productData as $productItem) {
            if (!isset($productItem['id'])) continue;

            $cartProduct = new Model\Cart\Product();
            $cartProduct->id = (string)$productItem['id'];
            $cartProduct->quantity = isset($productItem['quantity']) ? (int)$productItem['quantity'] : 0;

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
            $productItem = array_merge([
                'id'       => null,
                'ui'       => null,
                'quantity' => null,
                'parentId' => null,
                'added'    => null,
            ], $productItem);

            $cartProduct = new Model\Cart\Product();
            $cartProduct->id = (string)$productItem['id'];
            $cartProduct->ui = (string)$productItem['ui'];
            $cartProduct->quantity = (int)$productItem['quantity'];
            $cartProduct->parentId = $productItem['parentId'] ? (string)$productItem['parentId'] : null;
            $cartProduct->addedAt = $productItem['added'] ? (string)$productItem['added'] : null;

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
            $cartItem = [
                'id'       => $cartProduct->id,
                'ui'       => $cartProduct->ui,
                'quantity' => $cartProduct->quantity,
            ];
            if ($cartProduct->parentId) {
                $cartItem['parentId'] = $cartProduct->parentId;
            }

            if (!isset($cartData['product'][$cartProduct->id]['added'])) {
                $cartItem['added'] = date('c');
            } else {
                $cartItem = array_merge($cartData['product'][$cartProduct->id], $cartItem);
            }

            $cartData['product'][$cartProduct->id] = $cartItem;
        }
        $session->set('cart', $cartData);
    }

    /**
     * @param \EnterModel\Cart $cart
     * @param Query $query
     */
    public function updateObjectByQuery(Model\Cart $cart, Query $query) {
        $cartProductsById = [];
        foreach ($cart->product as $cartProduct) {
            $cartProductsById[$cartProduct->id] = $cartProduct;
        }

        $item = $query->getResult();
        $coreCart = new Model\Cart($item);

        $cart->sum = $coreCart->sum;
        foreach ($coreCart->product as $coreCartProduct) {
            /** @var \EnterModel\Cart\Product|null $cartProduct */
            $cartProduct = isset($cartProductsById[$coreCartProduct->id]) ? $cartProductsById[$coreCartProduct->id] : null;
            if (!$cartProduct) continue;

            $cartProduct->price = $coreCartProduct->price;
            $cartProduct->sum = $coreCartProduct->sum;
            $cartProduct->quantity = $coreCartProduct->quantity;
        }
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