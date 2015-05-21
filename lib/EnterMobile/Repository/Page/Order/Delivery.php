<?php

namespace EnterMobile\Repository\Page\Order;

use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterAggregator\PriceHelperTrait;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Partial;
use EnterMobile\Model\Page\Order\Delivery as Page;

class Delivery {
    use ConfigTrait, LoggerTrait, RouterTrait, TemplateHelperTrait, PriceHelperTrait;

    /**
     * @param Page $page
     * @param Delivery\Request $request
     */
    public function buildObjectByRequest(Page $page, Delivery\Request $request) {
        (new Repository\Page\DefaultPage)->buildObjectByRequest($page, $request);

        $router = $this->getRouter();
        $templateHelper = $this->getTemplateHelper();
        $priceHelper = $this->getPriceHelper();

        // заголовок
        $page->title = 'Оформление заказа - Способ получения - Enter';

        $page->dataModule = 'order';

        $page->content->region = [
            'name' => $request->region->name,
        ];

        $page->content->form->url = $router->getUrlByRoute(new Routing\Order\Create());
        $page->content->form->errorDataValue = $templateHelper->json($request->formErrors);

        $splitModel = $request->split;

        // индексация токенов методов доставки по группам доставки
        $deliveryMethodTokensByGroupToken = [];
        foreach ($splitModel->deliveryMethods as $deliveryMethodModel) {
            $deliveryMethodTokensByGroupToken[$deliveryMethodModel->groupId][] = $deliveryMethodModel->token;
        }

        $i = 1;
        foreach ($splitModel->orders as $orderModel) {
            $order = [
                'name'          => sprintf('Заказ №%s', $i),
                'seller'        =>
                    $orderModel->seller
                    ? [
                        'name' => $orderModel->seller->name,
                        'url'  => $orderModel->seller->offerUrl,
                    ]
                    : false,
                'sum'          => [
                    'name'  => $priceHelper->format($orderModel->sum),
                    'value' => $orderModel->sum,
                ],
                'pointSelected' => $orderModel->delivery && $orderModel->delivery->point,
                'deliveries'    => call_user_func(function() use (&$splitModel, &$templateHelper, &$orderModel, &$deliveryMethodTokensByGroupToken) {
                    $deliveries = [];

                    foreach ($splitModel->deliveryGroups as $deliveryGroupModel) {
                        $deliveryMethodToken =
                            isset($deliveryMethodTokensByGroupToken[$deliveryGroupModel->id][0])
                            ? $deliveryMethodTokensByGroupToken[$deliveryGroupModel->id][0]
                            : null
                        ;
                        if (!$deliveryMethodToken) continue;

                        $deliveries[] = [
                            'dataValue' => $templateHelper->json([
                                'methodToken' => $deliveryMethodToken,
                            ]),
                            'name'      => $deliveryGroupModel->name,
                            'active'    => $orderModel->delivery && in_array($orderModel->delivery->methodToken, $deliveryMethodTokensByGroupToken[$deliveryGroupModel->id]),
                        ];
                    }

                    return $deliveries;
                }),
            ];

            $page->content->orders[] = $order;

            $i++;
        }
    }
}