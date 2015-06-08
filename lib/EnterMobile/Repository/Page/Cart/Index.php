<?php

namespace EnterMobile\Repository\Page\Cart;

use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\TranslateHelperTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Partial;
use EnterMobile\Model\Page\Cart\Index as Page;

class Index {
    use ConfigTrait, LoggerTrait, RouterTrait, TranslateHelperTrait, TemplateHelperTrait;

    /**
     * @param Page $page
     * @param Index\Request $request
     */
    public function buildObjectByRequest(Page $page, Index\Request $request) {
        (new Repository\Page\DefaultPage)->buildObjectByRequest($page, $request);

        $config = $this->getConfig();
        $router = $this->getRouter();
        $templateHelper = $this->getTemplateHelper();

        $productCardRepository = new Repository\Partial\Cart\ProductCard();
        $productSpinnerRepository = new Repository\Partial\Cart\ProductSpinner();
        $productDeleteButtonRepository = new Repository\Partial\Cart\ProductDeleteButton();

        // заголовок
        $page->title = 'Корзина - Enter';

        $page->dataModule = 'cart';

        if (count($request->cart)) {
            $page->content->cart = (new Repository\Partial\Cart())->getObject($request->cart, $request->productsById);
        } else {
            $page->content->cart = false;
        }

        foreach (array_reverse($request->cartProducts) as $cartProduct) {
            $product = isset($request->productsById[$cartProduct->id]) ? $request->productsById[$cartProduct->id] : null;
            if (!$product) {
                // TODO: журналирование
                continue;
            }

            $productCard = $productCardRepository->getObject(
                $cartProduct,
                $product,
                $productSpinnerRepository->getObject($product, $cartProduct, false),
                $productDeleteButtonRepository->getObject($product)
            );
            $page->content->productBlock->products[] = $productCard;
        }

        // partner
        try {
            $page->partners = (new Repository\Partial\Partner())->getListForCart($request);
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['partner']]);
        }

        // шаблоны mustache
        (new Repository\Template())->setListForPage($page, [
            [
                'id'   => 'tpl-cart-productSum',
                'name' => 'partial/cart/productSum',
            ],
            [
                'id'   => 'tpl-cart-total',
                'name' => 'partial/cart/total',
            ],
            [
                'id'   => 'tpl-cart-bar',
                'name' => 'partial/cart/bar',
            ],
        ]);

        if (is_object($page->mailRu)) {
            $productIds = [];
            foreach ($request->cartProducts as $product) {
                $productIds[] = $product->id;
            }

            $page->mailRu->productIds = json_encode($productIds);
            $page->mailRu->pageType = 'cart';
            $page->mailRu->price = $request->cart->sum;
        }

        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}