<?php

namespace EnterMobile\Controller\Order\Slot;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\CurlTrait;
use EnterMobile\Repository;
use EnterQuery as Query;
use EnterModel as Model;

class Index {
    use ConfigTrait, LoggerTrait, SessionTrait, CurlTrait;

    /**
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $referer = isset($request->server['HTTP_REFERER']) ? $request->server['HTTP_REFERER'] : '/';
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);
        $cartSplitResponse = null;

        try {
            $phone = $this->getValidatedPhone($request->data['phone']);

            if ($request->data['confirm'] != '1') {
                throw new Exception('Подтвердите согласие с офертой');
            }

            $cartSplitResponse = $this->queryCartSplit($request->data['productId'], $regionId);
            $orderCreatePacketResponse = $this->queryOrderCreatePacket($cartSplitResponse, $request->data['productId'], $regionId, $phone, $request->data['email'], $request->data['name']);
        } catch (Exception $e) {
            return $request->isXmlHttpRequest() ? new Http\JsonResponse(['error' => $e->getMessage()]) : (new \EnterAggregator\Controller\Redirect())->execute($referer, 302);
        } catch (Query\CoreQueryException $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['curl', 'order/create']]);
            return $request->isXmlHttpRequest() ? new Http\JsonResponse(['error' => 708 == $e->getCode() ? 'Товара нет в наличии' : ($this->getConfig()->debugLevel ? $e->getMessage() : 'Ошибка при создании заявки')]) : (new \EnterAggregator\Controller\Redirect())->execute($referer, 302);
        } catch (\Exception $e) {
            if (!in_array($e->getCode(), $this->getConfig()->order->excludedError)) {
                $this->getLogger()->push([
                    'type' => 'error',
                    'error'   => ['code' => $e->getCode(), 'message' => $e->getMessage(), 'trace' => $e->getTraceAsString()],
                    'url'     => 'order/create-packet2',
                    'split'   => $cartSplitResponse,
                    'server'  => array_map(function($name) use (&$request) { return $request->server[$name]; }, [
                        'HTTP_USER_AGENT',
                        'HTTP_X_REQUESTED_WITH',
                        'HTTP_REFERER',
                        'HTTP_COOKIE',
                        'REQUEST_METHOD',
                        'QUERY_STRING',
                        'REQUEST_TIME_FLOAT',
                    ]),
                    'sender' => __FILE__ . ' ' .  __LINE__,
                    'tag' => ['order/create'],
                ]);
            }

            throw $e;
        }

        $result = [
            'orderNumber' => isset($orderCreatePacketResponse[0]['number_erp']) ? $orderCreatePacketResponse[0]['number_erp'] : null,
        ];

        return $request->isXmlHttpRequest() ? new Http\JsonResponse($result) : (new \EnterAggregator\Controller\Redirect())->execute($referer, 302);
    }

    private function getValidatedPhone($phone) {
        if (empty($phone)) {
            throw new Exception('Не указан телефон');
        }

        $phone = preg_replace('/^\+7/', '8', $phone);
        $phone = preg_replace('/[^\d]/', '', $phone);

        if (11 != strlen($phone)) {
            throw new Exception('Неверный номер телефона');
        }

        return $phone;
    }

    private function queryCartSplit($productId, $regionId) {
        $splitQuery = new Query\Cart\Split\GetItem(
            new Model\Cart([
                'product_list' => [
                    ['id' => $productId, 'quantity' => 1]
                ]
            ]),
            new Model\Region(['id' => $regionId])
        );

        $splitQuery->setTimeout($this->getConfig()->coreService->timeout * 2);

        $curl = $this->getCurl();
        $curl->prepare($splitQuery);
        $curl->execute();

        $splitResult = new Model\Cart\Split($splitQuery->getResult());
        if (!$splitResult->orders) {
            foreach ($splitResult->errors as $error) {
                if (708 == $error->code) {
                    throw new Exception('Товара нет в наличии');
                }
            }

            throw new \Exception('Отстуствуют данные по заказам');
        }

        return $splitResult;
    }

    private function queryOrderCreatePacket(Model\Cart\Split $cartSplitResponse, $productId, $regionId, $phone, $email, $name) {
        $createOrderQuery = new Query\Order\CreatePacketBySplit($cartSplitResponse, $this->getOrderCreatePacketMetas($cartSplitResponse, $productId, $regionId), false, Model\Order::TYPE_SLOT, $phone, $email, $name);
        $createOrderQuery->setTimeout(90);
        $this->getCurl()->query($createOrderQuery);
        return $createOrderQuery->getResult();
    }

    private function getOrderCreatePacketMetas(Model\Cart\Split $cartSplitResponse, $productId, $regionId) {
        $productListQuery = new Query\Product\GetListByIdList([$productId], $regionId);
        $this->getCurl()->query($productListQuery);

        $productsById = (new \EnterRepository\Product())->getIndexedObjectListByQueryList([$productListQuery]);

        $metas = [];
        // установка sender-а
        foreach ($cartSplitResponse->orders as $splitedOrder) {
            foreach ($splitedOrder->products as $orderProduct) {
                $product = isset($productsById[$orderProduct->id]) ? $productsById[$orderProduct->id] : null;
                if (!$product) continue;

                if (!empty($orderProduct->sender['name'])) {
                    $meta = new Model\Order\Meta();
                    $meta->key = 'product.' . $product->ui . '.' . 'sender';
                    $meta->value = $orderProduct->sender['name'];
                    $metas[] = $meta;
                }
            }
        }

        return $metas;
    }
}