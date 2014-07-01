<?php

namespace EnterSite\Repository;

use Enter\Http;
use EnterSite\ConfigTrait;
use EnterSite\Model;

class Compare {
    use ConfigTrait;

    /**
     * @param Http\Request $request
     * @return \EnterModel\Compare\Product|null
     */
    public function getProductObjectByHttpRequest(Http\Request $request) {
        $compareProduct = null;

        $productData = [
            'id' => null,
        ];
        if (!empty($request->query['product']['id'])) {
            $productData = array_merge($productData, $request->query['product']);
        } else if (!empty($request->data['product']['id'])) {
            $productData = array_merge($productData, $request->data['product']);
        }

        if ($productData['id']) {
            $compareProduct = new \EnterModel\Compare\Product();
            $compareProduct->id = (string)$productData['id'];
        }

        return $compareProduct;
    }

    /**
     * @param Http\Session $session
     * @return \EnterModel\Compare
     */
    public function getObjectByHttpSession(Http\Session $session) {
        $compare = new \EnterModel\Compare();

        $compareData = array_merge([
            'product' => [],
        ], (array)$session->get('compare'));

        foreach ($compareData['product'] as $productId => $productQuantity) {
            $compareProduct = new \EnterModel\Compare\Product();
            $compareProduct->id = (string)$productId;

            $compare->product[$compareProduct->id] = $compareProduct;
        }

        return $compare;
    }

    /**
     * @param Http\Session $session
     * @param \EnterModel\Compare $compare
     */
    public function saveObjectToHttpSession(Http\Session $session, \EnterModel\Compare $compare) {
        $compareData = [
            'product' => [],
        ];

        foreach ($compare->product as $compareProduct) {
            $compareData['product'][$compareProduct->id] = [
                'id' => $compareProduct->id,
            ];
        }

        $session->set('compare', $compareData);
    }

    /**
     * @param \EnterModel\Compare $compare
     * @param \EnterModel\Compare\Product $compareProduct
     */
    public function setProductForObject(\EnterModel\Compare $compare, \EnterModel\Compare\Product $compareProduct) {
        $compare->product[$compareProduct->id] = $compareProduct;
    }

    /**
     * @param \EnterModel\Compare $compare
     * @param \EnterModel\Compare\Product $compareProduct
     */
    public function deleteProductForObject(\EnterModel\Compare $compare, \EnterModel\Compare\Product $compareProduct) {
        if (array_key_exists($compareProduct->id, $compare->product)) {
            unset($compare->product[$compareProduct->id]);
        }
    }

    /**
     * @param $id
     * @param \EnterModel\Compare $compare
     * @return \EnterModel\Compare\Product|null
     */
    public function getProductById($id, \EnterModel\Compare $compare) {
        $return = null;

        foreach ($compare->product as $compareProduct) {
            if ($compareProduct->id === $id) {
                $return = $compareProduct;

                break;
            }
        }

        return $return;
    }

    /**
     * @param \EnterModel\Compare $compare
     * @param \EnterModel\Product[] $productsById
     */
    public function compareProductObjectList(\EnterModel\Compare $compare, array $productsById) {
        $compareFunction = function(\EnterModel\Product $product, \EnterModel\Product $productToCompare) {
            foreach ($product->properties as $property) {
                foreach ($productToCompare->properties as $propertyToCompare) {
                    if (!isset($property->equalProductIds)) {
                        $property->equalProductIds = [];
                    }

                    if ($property->id != $propertyToCompare->id) continue;

                    if ($property->value == $propertyToCompare->value) {
                        $property->equalProductIds[] = $productToCompare->id;
                    }
                }
            }
        };

        foreach ($compare->product as $comparedProduct) {
            /** @var \EnterModel\Product|null $product */
            $product = isset($productsById[$comparedProduct->id]) ? $productsById[$comparedProduct->id] : null;
            if (!$product) continue;

            // FIXME
            $product->compareGroupId = $product->category ? $product->category->id : null;

            foreach ($productsById as $productToCompare) {
                if ($product->id == $productToCompare->id) continue;
                $compareFunction($product, $productToCompare);
            }
        }
    }

    /**
     * @param \EnterModel\Compare $compare
     * @param \EnterModel\Product[] $productsById
     * @return \EnterModel\Compare\Group[]
     */
    public function getGroupListByObject(\EnterModel\Compare $compare, array $productsById) {
        $groupsById = [];

        foreach ($compare->product as $comparedProduct) {
            /** @var \EnterModel\Product|null $product */
            $product = isset($productsById[$comparedProduct->id]) ? $productsById[$comparedProduct->id] : null;
            if (!$product) continue;

            $groupId = $product->category ? $product->category->id : null;
            if (!$groupId || isset($groupsById[$groupId])) continue;

            $group = new \EnterModel\Compare\Group();
            $group->id = $groupId;
            $group->name = $product->category->name;

            $groupsById[$groupId] = $group;
        }

        return array_values($groupsById);
    }
}