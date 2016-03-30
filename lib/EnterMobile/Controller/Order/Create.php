<?php

namespace EnterMobile\Controller\Order;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\AbTestTrait;
use EnterAggregator\DebugContainerTrait;
use EnterModel as Model;
use EnterQuery as Query;
use EnterMobile\Routing;
use EnterMobile\Controller;
use EnterMobile\Repository;

class Create {
    use ConfigTrait, CurlTrait, SessionTrait, LoggerTrait, AbTestTrait, RouterTrait, DebugContainerTrait;
    use ControllerTrait {
        ConfigTrait::getConfig insteadof ControllerTrait;
    }

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
        $cartSessionKey = $this->getCartSessionKeyByHttpRequest($request);

        if (!isset($request->data['accept'])) {
            // TODO
        }

        // ид магазина
        $shopId = is_scalar($request->query['shopId']) ? (string)$request->query['shopId']: null;

        $splitData = (array)$session->get($config->order->splitSessionKey);

        if (!$splitData) {
            $this->getLogger()->push(['type' => 'error', 'error' => ['message' => 'Не найдено предыдущее разбиение'], 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['order', 'controller']]);

            $session->flashBag->set('orderForm.error', [
                ['message' => 'Корзина была обновлена']
            ]);

            // http-ответ
            return (new \EnterAggregator\Controller\Redirect())->execute(
                $router->getUrlByRoute(new Routing\Order\Delivery(), ['shopId' => $shopId]),
                302
            );
        }

        $response = (new \EnterAggregator\Controller\Redirect())->execute(
            $router->getUrlByRoute(new Routing\Order\Delivery(), ['shopId' => $shopId]),
            302
        );

        // ид региона
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        // запрос региона
        $regionQuery = new Query\Region\GetItemById($regionId);
        $curl->prepare($regionQuery);

        // токен пользователя
        $userToken = (new Repository\User())->getTokenBySessionAndHttpRequest($session, $request);

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

            // бонусные карты
            if ($cardData = $session->get($config->order->bonusCardSessionKey)) {
                foreach ($session->get($config->order->bonusCardSessionKey) as $cardItem) {
                    if (!isset($cardItem['type'])) continue;

                    if ('mnogoru' === $cardItem['type']) {
                        $meta = new Model\Order\Meta();
                        $meta->key = 'mnogo_ru_card';
                        $meta->value = $cardItem['number'];
                        $metas[] = $meta;
                    }
                }
            }
            // заголовки запроса
            if ($value = $request->getHeader('HTTP_USER_AGENT')) {
                $meta = new Model\Order\Meta();
                $meta->key = 'user_agent';
                $meta->value = $value;
                $metas[] = $meta;
            }
            // последний партнер
            if ($value = $request->cookies[$config->partner->cookieName]) {
                $meta = new Model\Order\Meta();
                $meta->key = 'last_partner';
                $meta->value = $value;
                $metas[] = $meta;
            }

            $controller = new \EnterAggregator\Controller\Order\Create();
            $controllerResponse = $controller->execute(
                $region->id,
                $split,
                $metas
            );

            if (!$controllerResponse->orders && $controllerResponse->errors) {
                $this->getLogger()->push(['type' => 'error', 'errors' => $controllerResponse->errors, 'tag' => ['critical', 'order']]);

                $session->flashBag->set('orderForm.error', $controllerResponse->errors);

                if ($error = reset($controllerResponse->errors)) {
                    if (in_array($error['code'], [759])) { // Некорректный email
                        $response = (new \EnterAggregator\Controller\Redirect())->execute(
                            $router->getUrlByRoute(new Routing\Order\Index(), ['shopId' => $shopId]),
                            302
                        );
                    }

                    throw new \Exception($error['message'], (int)$error['code']);
                }

                throw new \Exception('Заказы не созданы');
            }

            // http-ответ
            $response = (new \EnterAggregator\Controller\Redirect())->execute(
                $router->getUrlByRoute(new Routing\Order\Complete()),
                302
            );

            $session->remove($config->order->splitSessionKey);
            $session->remove($config->order->bonusCardSessionKey);
            $session->remove($cartSessionKey);

            // серверная корзина
            if ($user && $this->getAbTest()->isCoreCartEnabled()) {
                /** @var Query\Cart\DeleteProductItem[] $removeQueries */
                $removeQueries = [];
                foreach ($controllerResponse->orders as $order) {
                    foreach ($order->product as $orderProduct) {
                        $removeQuery = new Query\Cart\DeleteProductItem($orderProduct->ui, $user->ui);
                        $curl->prepare($removeQuery);
                        $removeQueries[] = $removeQuery;
                    }
                }

                $curl->execute(null, 1);
            }

            $orderData = [
                'updatedAt' => (new \DateTime())->format('c'),
                'expired'   => false,
                'orders'    => json_decode(json_encode($controllerResponse->orders), true),
            ];

            $session->set($config->order->sessionName, $orderData);

            $this->getLogger()->push(['message' => 'Order created successfully', 'tag' => ['critical', 'order.create']]);
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'tag' => ['critical', 'order.create']]);

            // TODO: flash message
        }

        return $response;
    }
}