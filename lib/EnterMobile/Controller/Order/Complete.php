<?php

namespace EnterMobile\Controller\Order;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\DebugContainerTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterModel as Model;
use EnterQuery as Query;
use EnterMobile\Controller;
use EnterMobile\Repository;
use EnterMobile\Routing;
use EnterMobile\Model\Page\Order\Complete as Page;

class Complete {
    use ConfigTrait, CurlTrait, SessionTrait, LoggerTrait, RouterTrait, MustacheRendererTrait, DebugContainerTrait;

    /**
     * @param Http\Request $request
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();
        $session = $this->getSession();
        $router = $this->getRouter();

        $regionRepository = new \EnterRepository\Region();

        // ид региона
        $regionId = $regionRepository->getIdByHttpRequestCookie($request);

        // запрос региона
        $regionQuery = new Query\Region\GetItemById($regionId);
        $curl->prepare($regionQuery);

        // запрос пользователя
        $userItemQuery = (new \EnterMobile\Repository\User())->getQueryByHttpRequest($request);
        if ($userItemQuery) {
            $curl->prepare($userItemQuery);
        }

        $cart = (new \EnterRepository\Cart())->getObjectByHttpSession($this->getSession(), $config->cart->sessionKey);
        $cartItemQuery = (new \EnterMobile\Repository\Cart())->getPreparedCartItemQuery($cart, $regionId);
        $cartProductListQuery = (new \EnterMobile\Repository\Cart())->getPreparedCartProductListQuery($cart, $regionId);

        $curl->execute();

        try {
            $region = $regionRepository->getObjectByQuery($regionQuery);
        } catch (\Exception $e) {
            $region = new Model\Region(['id' => $config->region->defaultId, 'name' => 'Москва*']);
        }

        (new \EnterRepository\Cart())->updateObjectByQuery($cart, $cartItemQuery, $cartProductListQuery);

        /** @var Model\Order[] $orders */
        $orders = [];
        try {
            $orderData = ($session->get($config->order->sessionName) ?: []) + [
                'updatedAt' => null,
                'expired'   => null,
                'orders'    => [],
            ];
            // FIXME fixture
            //die(json_encode($orderData, JSON_UNESCAPED_UNICODE));
            //$orderData = json_decode('{"updatedAt":"2015-06-15T15:38:08+03:00","expired":false,"orders":[{"number":"TG071064","sum":3980,"delivery":{"type":{"token":"self","shortName":"Самовывоз"},"price":0,"date":1434402000},"interval":{"from":"16:00","to":"21:00"},"paymentMethodId":"1","point":{"ui":"57ba26a3-ea68-11e0-83b4-005056af265b"}}]}', true);
            //die(var_dump($orderData));

            $pointUis = [];
            $orderNumberErps = [];
            foreach ($orderData['orders'] as $orderItem) {
                $order = new Model\Order();
                $order->sum = $orderItem['sum'];
                $order->id = $orderItem['id'];
                $order->number = $orderItem['number'];
                $order->numberErp = $orderItem['numberErp'];
                if (!empty($orderItem['delivery'])) {
                    $delivery = new Model\Order\Delivery();
                    try {
                        $delivery->date = !empty($orderItem['delivery']['date']) ? $orderItem['delivery']['date'] : null;
                    } catch (\Exception $e) {
                        $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['order', 'critical']]);
                    }

                    if (!empty($orderItem['delivery']['type']['shortName'])) {
                        $delivery->type = new Model\DeliveryType();
                        $delivery->type->token = $orderItem['delivery']['type']['token'];
                        $delivery->type->shortName = $orderItem['delivery']['type']['shortName'];
                    }
                    if (!empty($orderItem['point']['ui'])) {
                        $order->point = new Model\Point($orderItem['point']);
                        $pointUis[] = $orderItem['point']['ui'];
                    }
                    if (!empty($orderItem['interval'])) {
                        $interval = new Model\Order\Interval($orderItem['interval']);
                        $interval->from = $orderItem['interval']['from'];
                        $interval->to = $orderItem['interval']['to'];
                        $order->interval = $interval;
                    }
                    if (!empty($orderItem['product'][0])) {
                        foreach ($orderItem['product'] as $productItem) {
                            if (empty($productItem['id'])) continue;

                            $product = new Model\Order\Product($productItem);
                            if (isset($productItem['name'])) {
                                $product->name = $productItem['name'];
                            }
                            if (isset($productItem['link'])) {
                                $product->link = $productItem['link'];
                            }

                            $order->product[] = $product;
                        }
                    }

                    $orderNumberErps[] = $order->numberErp;

                    $order->deliveries[] = $delivery;
                }

                $orders[] = $order;
            }

            if (0 === count($orders)) {
                //return (new \EnterAggregator\Controller\Redirect())->execute($router->getUrlByRoute(new Routing\Cart\Index()), 302);
            }

            /** @var Model\PaymentMethod[] $onlinePaymentMethodsById */
            $onlinePaymentMethodsById = [];
            try {
                // дополнение точками самовывоза
                $pointListQuery = null;
                if ($pointUis) {
                    $pointListQuery = new Query\Point\GetList($pointUis);
                    $pointListQuery->setTimeout(1.5 * $config->coreService->timeout);
                    $curl->prepare($pointListQuery);
                }

                // онлайн оплата
                foreach ($orderNumberErps as $orderNumberErp) {
                    $paymentListQuery = new Query\Payment\GetListByOrderNumberErp($region->id, $orderNumberErp);
                    $curl->prepare($paymentListQuery);
                    $paymentListQueriesByNumberErp[$orderNumberErp] = $paymentListQuery;
                }

                $curl->execute();

                /** @var Model\Point[] $pointsByUi */
                $pointsByUi = [];
                if ($pointListQuery) {
                    foreach ($pointListQuery->getResult() as $pointItem) {
                        if (!isset($pointItem['ui'])) continue;

                        $point = new Model\Point($pointItem);

                        $pointsByUi[$point->ui] = $point;
                    }
                }

                foreach ($orders as $order) {
                    if ($order->point && isset($pointsByUi[$order->point->ui])) {
                        $order->point = $pointsByUi[$order->point->ui];
                    }

                    try {
                        /** @var Query\Payment\GetListByOrderNumberErp|null $paymentListQuery */
                        $paymentListQuery = isset($paymentListQueriesByNumberErp[$order->numberErp]) ? $paymentListQueriesByNumberErp[$order->numberErp] : null;
                        if ($paymentListQuery) {
                            $paymentData = $paymentListQuery->getResult()['methods'];

                            foreach ($paymentData as $paymentItem) {
                                $paymentMethod = new Model\PaymentMethod($paymentItem);
                                if (!$paymentMethod->isOnline) continue;

                                $onlinePaymentMethodsById[$paymentMethod->id] = $paymentMethod;
                            }
                        }
                    } catch (\Exception $e) {
                        $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['order', 'critical']]);
                    }
                }

            } catch (\Exception $e) {
                $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['order', 'critical']]);
            }

        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['order', 'critical']]);
        }

        // запрос для получения страницы
        $pageRequest = new Repository\Page\Order\Complete\Request();
        $pageRequest->httpRequest = $request;
        $pageRequest->user = (new \EnterMobile\Repository\User())->getObjectByQuery($userItemQuery);
        $pageRequest->cart = $cart;
        $pageRequest->orders = $orders;
        $pageRequest->onlinePaymentMethodsById = $onlinePaymentMethodsById;
        //die(json_encode($pageRequest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // страница
        $page = new Page();
        (new Repository\Page\Order\Complete())->buildObjectByRequest($page, $pageRequest);

        // debug
        if ($config->debugLevel) $this->getDebugContainer()->page = $page;
        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        // рендер
        $renderer = $this->getRenderer();
        $renderer->setPartials([
            'content' => 'page/order/complete/content',
        ]);
        $content = $renderer->render('layout/simple', $page);

        // http-ответ
        $response = new Http\Response($content);

        return $response;
    }
}