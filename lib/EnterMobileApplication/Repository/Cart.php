<?php

namespace EnterMobileApplication\Repository;

use Enter\Http;
use EnterModel as Model;

class Cart {
    /**
     * @param Model\Cart $cart
     * @param bool $returnProducts
     * @return array
     */
    public function getResponseArray($cart, $returnProducts = false) {
        $response = [];

        if ($returnProducts) {
            $response['sum'] = $cart->sum;
        }

        $response['quantity'] = 0;
        $response['uniqueQuantity'] = 0;
        
        if ($returnProducts) {
            $response['products'] = [];
        }

        $helper = new \Enter\Helper\Template();
        $productRepository = new \EnterMobileApplication\Repository\Product();

        foreach (array_reverse($cart->product) as $cartProduct) {
            /** @var Model\Cart\Product $cartProduct */

            $response['quantity'] += $cartProduct->quantity;
            $response['uniqueQuantity']++;
            
            if ($returnProducts) {
                $product = [
                    'id' => $cartProduct->id,
                    'quantity' => $cartProduct->quantity,
                    'sum' => $cartProduct->sum,
                ];

                if ($cartProduct->product) {
                    $product['webName'] = $helper->unescape($cartProduct->product->webName);
                    $product['namePrefix'] = $helper->unescape($cartProduct->product->namePrefix);
                    $product['name'] = $helper->unescape($cartProduct->product->name);
                    $product['price'] = $cartProduct->product->price;
                    $product['media'] = $productRepository->getMedia($cartProduct->product);
                }

                $response['products'][] = $product;
            }
        }
        
        return $response;
    }
}