<?php

namespace EnterSite\Repository\Partial;

use EnterSite\ConfigTrait;
use EnterSite\ViewHelperTrait;
use EnterSite\Repository;
use EnterSite\Model;
use EnterSite\Model\Partial;

class DirectCredit {
    use ConfigTrait, ViewHelperTrait;

    /**
     * @param \EnterModel\Product[] $products
     * @param \EnterModel\Cart|null $cartModel
     * @return Partial\DirectCredit
     */
    public function getObject(
        $products = [],
        \EnterModel\Cart $cartModel = null
    ) {
        $directCredit = new Partial\DirectCredit();

        $cartProductsById = [];
        if ($cartModel) {
            foreach ($cartModel->product as $cartProduct) {
                $cartProductsById[$cartProduct->id] = $cartProduct;
            }
        }

        $productData = [];
        foreach ($products as $product) {
            /** @var \EnterModel\Product\Category|null $rootCategory */
            $rootCategory = ($product->category && !empty($product->category->ascendants[0])) ? $product->category->ascendants[0] : null;
            /** @var \EnterModel\Cart\Product|null $cartProduct */
            $cartProduct = !empty($productCartsById[$product->id]) ? $productCartsById[$product->id] : null;

            $productData[] = [
                'id'    => $product->id,
                'name'  => $product->name,
                'price' => $product->price,
                'count' => $cartProduct ? $cartProduct->quantity : 1,
                'type'  => $rootCategory ? (new Repository\DirectCredit())->getTypeByCategoryToken($rootCategory->token) : null,
            ];
        }

        $directCredit->widgetId = 'id-creditPayment';
        $directCredit->dataValue = $this->getViewHelper()->json([
            'partnerId' => $this->getConfig()->directCredit->partnerId,
            'product'   => $productData,
        ]);
        $directCredit->isHidden = $cartModel ? !(new Repository\DirectCredit())->isEnabledForCart($cartModel) : false;

        return $directCredit;
    }

    /**
     * @return string
     */
    public static function getWidgetId() {
        return 'id-directCredit';
    }
}