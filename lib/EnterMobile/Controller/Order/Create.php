<?php

namespace EnterMobile\Controller\Order;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\DebugContainerTrait;
use EnterModel as Model;
use EnterQuery as Query;
use EnterMobile\Routing;
use EnterMobile\Controller;
use EnterMobile\Repository;

class Create {
    use ConfigTrait, CurlTrait, SessionTrait, LoggerTrait, RouterTrait, DebugContainerTrait;

    /**
     * @param Http\Request $request
     * @return Http\Response
     * @throws \Exception
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();
        $session = $this->getSession();
        $router = $this->getRouter();
        $cartRepository = new \EnterRepository\Cart();

        if (!isset($request->data['accept'])) {
            // TODO
        }

        $splitData = (array)$session->get($config->order->splitSessionKey);

        try {
            if (!$splitData) {
                throw new \Exception('Не найдено предыдущее разбиение');
            }

            if (!isset($splitData['cart']['product_list'])) {
                throw new \Exception('Не найдены товары в корзине');
            }
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'warn', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['order', 'controller']]);

            // http-ответ
            return (new \EnterAggregator\Controller\Redirect())->execute(
                $router->getUrlByRoute(new Routing\Cart\Index()),
                302
            );
        }

        $response = (new \EnterAggregator\Controller\Redirect())->execute(
            $router->getUrlByRoute(new Routing\Order\Delivery()),
            302
        );

        // ид региона
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        // запрос региона
        $regionQuery = new Query\Region\GetItemById($regionId);
        $curl->prepare($regionQuery);

        // токен пользователя
        $userToken = (new Repository\User())->getTokenByHttpRequest($request);

        // запрос пользователя
        $userItemQuery = null;
        if ($userToken && (0 !== strpos($userToken, 'anonymous-'))) {
            $userItemQuery = new Query\User\GetItemByToken($userToken);
            $curl->prepare($userItemQuery);
        }

        $curl->execute();

        $region = (new \EnterRepository\Region())->getObjectByQuery($regionQuery);

        // пользователь
        $user = null;
        try {
            if ($userItemQuery) {
                $user = (new \EnterRepository\User())->getObjectByQuery($userItemQuery);
            }
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller']]);
        }

        // корзина из данных о разбиении
        $cart = new Model\Cart();
        foreach ($splitData['cart']['product_list'] as $productItem) {
            $cartProduct = new Model\Cart\Product($productItem);
            $cartRepository->setProductForObject($cart, $cartProduct);
        }

        $split = null;
        try {
            $split = new Model\Cart\Split($splitData);

            // дополнительные свойства разбиения
            $split->region = $region;
            $split->clientIp = $request->getClientIp();

            // пользователь
            if ($user) {
                $split->user->id = $user->id;
                $split->user->ui = $user->ui;
            }

            // meta
            $metas = [];

            $controller = new \EnterAggregator\Controller\Order\Create();
            $controllerResponse = $controller->execute(
                $region->id,
                $split,
                $metas
            );

            // http-ответ
            $response = (new \EnterAggregator\Controller\Redirect())->execute(
                $router->getUrlByRoute(new Routing\Order\Complete()),
                302
            );

            // TODO: удалить предыдущее разбиение и очистить корзину!!!

            $orderData = [
                'updatedAt' => (new \DateTime())->format('c'),
                'expired'   => false,
                'orders'    => call_user_func(function() use (&$controllerResponse) {
                    $orders = [];

                    foreach ($controllerResponse->orders as $order) {
                        $orders[] = [
                            'id'              => $order->id,
                            'number'          => $order->number,
                            'numberErp'       => $order->numberErp,
                            'sum'             => $order->sum,
                            'delivery'        =>
                                isset($order->deliveries[0])
                                ? call_user_func(function() use ($order) {
                                    $delivery = $order->deliveries[0];

                                    return [
                                        'type'  =>
                                            $delivery->type
                                            ? [
                                                'token'     => $delivery->type->token,
                                                'shortName' => $delivery->type->shortName,
                                            ]
                                            : null
                                        ,
                                        'price' => $delivery->price,
                                        'date'  => $delivery->date,
                                    ];
                                })
                                : null
                            ,
                            'interval'        =>
                                $order->interval
                                ? ['from' => $order->interval->from, 'to' => $order->interval->to]
                                : null
                            ,
                            'paymentMethodId' => $order->paymentMethodId,
                            'point'           =>
                                $order->point
                                ? [
                                    'ui' => $order->point->ui,
                                ]
                                : null
                            ,
                            'product' => call_user_func(function() use (&$order) {
                                $data = [];

                                foreach ($order->product as $product) {
                                    $data[] = [
                                        'id'       => $product->id,
                                        'quantity' => $product->quantity,
                                        'name'     => isset($product->name) ? $product->name : null,
                                        'link'     => isset($product->link) ? $product->link : null,
                                    ];
                                }

                                return $data;
                            }),
                        ];
                    }

                    return $orders;
                }),
            ];

            $session->set($config->order->sessionName, $orderData);
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'tag' => ['critical', 'order']]);

            // TODO: flash message

            throw new \Exception($e->getMessage());
        }

        return $response;
    }
}