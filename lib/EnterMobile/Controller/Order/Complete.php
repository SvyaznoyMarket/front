<?php

namespace EnterMobile\Controller\Order;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\DebugContainerTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterModel as Model;
use EnterQuery as Query;
use EnterMobile\Controller;
use EnterMobile\Repository;
use EnterMobile\Model\Page\Order\Complete as Page;

class Complete {
    use ConfigTrait, CurlTrait, SessionTrait, LoggerTrait, MustacheRendererTrait, DebugContainerTrait;

    /**
     * @param Http\Request $request
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();
        $session = $this->getSession();

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
            $orderData = json_decode('{"updatedAt":"2015-06-15T15:38:08+03:00","expired":false,"orders":[{"number":"TG071064","sum":3980,"delivery":{"type":{"token":"self","shortName":"Самовывоз"},"price":0,"date":1434402000},"interval":{"from":"16:00","to":"21:00"},"paymentMethodId":"1","point":{"ui":"57ba26a3-ea68-11e0-83b4-005056af265b"}}]}', true);
            //die(var_dump($orderData));

            $pointUis = [];
            foreach ($orderData['orders'] as $orderItem) {
                $order = new Model\Order();
                $order->number = $orderItem['number'];
                $order->sum = $orderItem['sum'];
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

                    $order->deliveries[] = $delivery;
                }

                $orders[] = $order;
            }

            try {
                // дополнение точками самовывоза
                if ($pointUis) {
                    $pointListQuery = new Query\Point\GetList($pointUis);
                    $pointListQuery->setTimeout(1.5 * $config->coreService->timeout);
                    $curl->prepare($pointListQuery);

                    $curl->execute();

                    /** @var Model\Point[] $pointsByUi */
                    $pointsByUi = [];
                    foreach ($pointListQuery->getResult() as $pointItem) {
                        if (!isset($pointItem['ui'])) continue;

                        $point = new Model\Point($pointItem);

                        $pointsByUi[$point->ui] = $point;
                    }

                    foreach ($orders as $order) {
                        if ($order->point && isset($pointsByUi[$order->point->ui])) {
                            $order->point = $pointsByUi[$order->point->ui];
                        }
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
        $pageRequest->orders = $orders;
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