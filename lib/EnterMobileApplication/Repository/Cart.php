<?php

namespace EnterMobileApplication\Repository;

use Enter\Http;
use EnterModel as Model;

class Cart {
    /**
     * @param Model\Cart $cart
     * @param Model\Product[] $productsById
     * @return array
     */
    public function getResponseArray($cart, $productsById = []) {
        $response = [];
        if ($cart->sum !== null) {
            $response['sum'] = $cart->sum;
        }
        
        $response['quantity'] = 0;
        $response['uniqueQuantity'] = 0;
        
        if ($productsById) {
            $response['products'] = [];
        }

        foreach (array_reverse($cart->product) as $cartProduct) {
            /** @var Model\Cart\Product $cartProduct */

            $response['quantity'] += $cartProduct->quantity;
            $response['uniqueQuantity']++;
            
            if ($productsById) {
                $product = new \EnterMobileApplication\Model\Cart\Product();

                $product->id = $cartProduct->id;
                $product->quantity = $cartProduct->quantity;
                $product->sum = $cartProduct->sum;

                if (!empty($productsById[$cartProduct->id])) {
                    $product->webName = $productsById[$cartProduct->id]->webName;
                    $product->namePrefix = $productsById[$cartProduct->id]->namePrefix;
                    $product->name = $productsById[$cartProduct->id]->name;
                    $product->price = $productsById[$cartProduct->id]->price;
                    $product->media = $productsById[$cartProduct->id]->media;
                }

                $response['products'][] = $product;
            }
        }
        
        return $response;
    }
}