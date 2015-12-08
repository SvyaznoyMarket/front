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
    use ControllerTrait {
        ConfigTrait::getConfig insteadof ControllerTrait;
    }

    /**
     * @param Http\Request $request
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();
        $session = $this->getSession();
        $cartSessionKey = $this->getCartSessionKeyByHttpRequest($request);

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

        $curl->execute();

        $region = $regionRepository->getObjectByQuery($regionQuery);
        
        $cart = (new \EnterRepository\Cart())->getObjectByHttpSession($this->getSession(), $cartSessionKey);
        $cartItemQuery = (new \EnterMobile\Repository\Cart())->getPreparedCartItemQuery($cart, $region->id);
        $cartProductListQuery = (new \EnterMobile\Repository\Cart())->getPreparedCartProductListQuery($cart, $region->id);
        
        $curl->execute();

        (new \EnterRepository\Cart())->updateObjectByQuery($cart, $cartItemQuery, $cartProductListQuery);

        /** @var Model\Order[] $orders */
        $orders = [];
        /** @var Model\PaymentMethod[] $onlinePaymentMethodsById */
        $onlinePaymentMethodsById = [];
        /** @var array $orderData */
        $orderData = ($session->get($config->order->sessionName) ?: []) + [
            'updatedAt'            => null,
            'expired'              => null,
            'isCompletePageReaded' => false,
            'orders'               => [],
        ];
        try {
            $session->set($config->order->sessionName, array_merge($orderData, ['isCompletePageReaded' => true]));

            $pointUis = [];
            $orderNumberErps = [];
            foreach ($orderData['orders'] as $orderItem) {
                if (empty($orderItem['numberErp'])) {
                    $this->getLogger()->push(['type' => 'error', 'error' => ['message' => 'Некорректные данные'], 'orderItem' => $orderItem, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['order', 'critical']]);
                    continue;
                }

                $order = new Model\Order();
                $order->fromArray($orderItem);

                $orderNumberErps[] = $order->numberErp;
                if ($order->point) {
                    $pointUis[] = $order->point->ui;
                }

                $orders[] = $order;
            }

            if (0 === count($orders)) {
                //return (new \EnterAggregator\Controller\Redirect())->execute($router->getUrlByRoute(new Routing\Cart\Index()), 302);
            }

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
                            $paymentData = $paymentListQuery->getResult()['methods'] ?: [];

                            foreach ($paymentData as $paymentItem) {
                                $paymentMethod = new Model\PaymentMethod($paymentItem);
                                // MAPI-179
                                if (!$paymentMethod->sum) {
                                    $paymentMethod->sum = $order->paySum;
                                }

                                if (
                                    !$paymentMethod->isOnline
                                    || $paymentMethod->isCredit
                                ) {
                                    continue;
                                }

                                $onlinePaymentMethodsById[$paymentMethod->id] = $paymentMethod;
                                $order->paymentMethods[] = $paymentMethod;
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
        $pageRequest->isCompletePageReaded = $orderData['isCompletePageReaded'];
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