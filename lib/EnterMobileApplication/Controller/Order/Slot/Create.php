<?php

namespace EnterMobileApplication\Controller\Order\Slot;

use Enter\Http;
use EnterMobileApplication\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\CurlTrait;
use EnterMobileApplication\Repository;
use EnterQuery as Query;
use EnterModel as Model;

class Create {
    use ConfigTrait, LoggerTrait, SessionTrait, CurlTrait;

    /**
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);
        $userToken = (new \EnterRepository\User)->getTokenByHttpRequest($request);
        $curl = $this->getCurl();
        $cartSplitResponse = null;

        try {
            $phone = $this->getValidatedPhone($request->data['phone']);

            if ($request->data['confirm'] != '1') {
                throw new Exception('Подтвердите согласие с офертой');
            }

            // запрос пользователя
            $userItemQuery = $userToken ? new Query\User\GetItemByToken($userToken) : null;
            if ($userItemQuery) {
                $curl->prepare($userItemQuery);
            }

            // запрос cart/split
            $splitQuery = new Query\Cart\Split\GetItem(
                new Model\Cart([
                    'product_list' => [
                        ['id' => $request->data['productId'], 'quantity' => 1]
                    ]
                ]),
                new Model\Region(['id' => $regionId])
            );
            $splitQuery->setTimeout($this->getConfig()->coreService->timeout * 2);
            $curl->prepare($splitQuery);

            $curl->execute();

            $user = $userItemQuery ? (new \EnterRepository\User())->getObjectByQuery($userItemQuery) : null;

            $cartSplitResponse = new Model\Cart\Split($splitQuery->getResult());
            $cartSplitResponse->region = new Model\Region(['id' => $regionId]);
            if (!$cartSplitResponse->orders) {
                foreach ($cartSplitResponse->errors as $error) {
                    if (708 == $error->code) {
                        throw new Exception('Товара нет в наличии');
                    }
                }

                throw new \Exception('Отстуствуют данные по заказам');
            }

            $orderCreatePacketResponse = $this->queryOrderCreatePacket($cartSplitResponse, $request->data['productId'], $regionId, $phone, $request->data['email'], $request->data['name'], $user);
        } catch (Exception $e) {
            return new Http\JsonResponse(['error' => $e->getMessage()]);
        } catch (Query\CoreQueryException $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['curl', 'order/create']]);

            if (708 == $e->getCode()) {
                $errorMessage = 'Товара нет в наличии';
            } else if (720 == $e->getCode()) {
                $errorMessage = 'Это дублирующий заказ';
            } else if ($this->getConfig()->debugLevel) {
                $errorMessage = $e->getMessage();
            } else {
                $errorMessage = 'Ошибка при создании заявки';
            }

            return new Http\JsonResponse(['error' => $errorMessage]);
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

        return new Http\JsonResponse([
            'orderNumber' => isset($orderCreatePacketResponse[0]['number_erp']) ? $orderCreatePacketResponse[0]['number_erp'] : null,
        ]);
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

    private function queryOrderCreatePacket(Model\Cart\Split $cartSplitResponse, $productId, $regionId, $phone, $email, $name, Model\User $user = null) {
        $createOrderQuery = new Query\Order\CreatePacketBySplit($cartSplitResponse, $this->getOrderCreatePacketMetas($cartSplitResponse, $productId, $regionId), false, Model\Order::TYPE_SLOT, $phone, $email, $name, $user);
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