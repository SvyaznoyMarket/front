<?php

namespace EnterMobile\Repository\Partial;

use EnterAggregator\SessionTrait;
use EnterMobile\ConfigTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Partial;

class DirectCredit {
    use ConfigTrait, TemplateHelperTrait, SessionTrait;

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
        $categoryRepository = new \EnterRepository\Product\Category();

        if (!$this->getConfig()->credit->enabled) {
            return $directCredit;
        }

        $cartProductsById = [];
        if ($cartModel) {
            foreach ($cartModel->product as $cartProduct) {
                $cartProductsById[$cartProduct->id] = $cartProduct;
            }
        }

        $productData = [];
        foreach ($products as $product) {
            /** @var \EnterModel\Product\Category|null $rootCategory */
            $rootCategory = $product->category ? $categoryRepository->getRootObject($product->category) : null;
            /** @var \EnterModel\Cart\Product|null $cartProduct */
            $cartProduct = !empty($cartProductsById[$product->id]) ? $cartProductsById[$product->id] : null;

            $productData[] = [
                'id'    => $product->id,
                'name'  => $product->name,
                'price' => $product->price,
                'count' => $cartProduct ? $cartProduct->quantity : 1,
                'type'  => $rootCategory ? (new \EnterRepository\DirectCredit())->getTypeByCategoryToken($rootCategory->token) : null,
            ];
        }

        $directCredit->widgetId = 'id-creditPayment';
        $directCredit->dataValue = $this->getTemplateHelper()->json([
            'partnerId' => $this->getConfig()->credit->directCredit->partnerId,
            'sessionId' => $this->getSession()->getId(),
            'product'   => $productData,
        ]);
        $directCredit->isHidden = $cartModel ? !(new \EnterRepository\DirectCredit())->isEnabledForCart($cartModel) : false; // FIXME

        return $directCredit;
    }

    /**
     * @return string
     */
    public static function getWidgetId() {
        return 'id-directCredit';
    }
}