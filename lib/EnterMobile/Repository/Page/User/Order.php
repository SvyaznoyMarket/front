<?php

namespace EnterMobile\Repository\Page\User;

use EnterAggregator\CurlTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\PriceHelperTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterAggregator\DateHelperTrait;
use EnterMobile\ConfigTrait;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Partial;
use EnterMobile\Model\Page\User\Order as Page;


class Order {
    use LoggerTrait,
        TemplateHelperTrait,
        RouterTrait,
        CurlTrait,
        ConfigTrait,
        DateHelperTrait,
        PriceHelperTrait;

    /**
     * @param Page $page
     * @param Index\Request $request
     */
    public function buildObjectByRequest(Page $page, Order\Request $request) {
        (new Repository\Page\DefaultPage)->buildObjectByRequest($page, $request);

        $templateHelper = $this->getTemplateHelper();
        $page->title = 'Заказ';

        // ga
        $walkByMenu = function(array $menuElements) use(&$walkByMenu, &$templateHelper) {
            /** @var \EnterModel\MainMenu\Element[] $menuElements */
            foreach ($menuElements as $menuElement) {
                $menuElement->dataGa = $templateHelper->json([
                    'm_main_category' => ['send', 'event', 'm_main_category', $menuElement->name],
                ]);
                /*
                if ((bool)$menuElement->children) {
                    $walkByMenu($menuElement->children);
                }
                */
            }
        };
        $walkByMenu($request->mainMenu->elements);

        if ($request->order->point) {
            $mediaList = $request->order->point['media'];
            $request->order->point['logo']= (new \EnterRepository\Media())->getSourceObjectByList($mediaList->photos, 'logo', '100x100')->url;
        }

        $request->order->createdAt = $this->getDateHelper()->strftimeRu('%e.%m.%Y', $request->order->createdAt);

        if (is_array($request->order->product) && !empty(($request->order->product))) {
            foreach ($request->order->product as $product) {
                $mediaList = $product->media;
                $product->media = (new \EnterRepository\Media())->getSourceObjectByList($mediaList->photos, 'main', 'product_60')->url;

                $product->price = $this->getPriceHelper()->format($product->price);
                $product->sum = $this->getPriceHelper()->format($product->sum);
            }
        }


        $request->order->product = array_values($request->order->product);


        // доставка
        $deliveryInfo = [];

        if (!empty($request->order->deliveries)) {
            foreach ($request->order->deliveries as $delivery) {
                if ($delivery->type->token == 'self' || $request->order->deliveryType == 'self') {
                    $request->order->isDelivery = false;
                } else {
                    $request->order->isDelivery = true;
                }

                if ($delivery->type->shortName) {
                    $deliveryInfo['name'] = $delivery->type->shortName;
                } else {
                    $deliveryInfo['name'] = ($delivery->type->token == 'self' || $request->order->deliveryType == 'self') ?
                        'Самовывоз' :
                        'Доставка'
                    ;
                }

                $deliveryInfo['date'] = $this->getDateHelper()->strftimeRu('%e.%m.%Y', $delivery->date);
                $deliveryInfo['price'] = $delivery->price;
            }
        } else {
            // если массив с доставками пустой - нужно проверить
            // 1) стоимость доставки в метаданных
            // 2) тип доставки
            foreach ($request->order->meta as $metaData) {
                if ($metaData->key != 'delivery_price') continue;

                $deliveryInfo['price'] = $metaData->value[0];
            }

            switch ($request->order->deliveryType) {
                case 'self':
                case 'now':
                    $deliveryInfo['name'] = 'Самовывоз';
                    $request->order->isDelivery = false;
                    break;
                case 'standart':
                case 'express':
                case 'standart_svyaznoy':
                default:
                    $deliveryInfo['name'] = 'Доставка';
                    $request->order->isDelivery = true;
                    break;
            }
        }

        $request->order->deliveries = $deliveryInfo;

        $page->content->order = $request->order;

        // шаблоны mustache
        // ...

        (new Repository\Template())->setListForPage($page, [

        ]);

        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}