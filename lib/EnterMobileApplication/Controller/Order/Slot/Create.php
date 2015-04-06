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
        $userItemQuery = null;
        $cartSplitQuery = null;
        $createOrderQuery = null;

        try {
            $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request);
            if (!$regionId) {
                throw new \Exception('Не передан параметр regionId');
            }

            if (!$request->data['productId']) {
                throw new \Exception('Не передан productId');
            }

            if ($request->data['confirm'] != '1') {
                throw new \Exception('Не подтверждено согласие с офертой');
            }

            $userItemQuery = $this->prepareUserItemQuery($request->query['userToken']);
            $cartSplitQuery = $this->prepareCartSplitQuery($request->data['productId'], $regionId);

            $this->getCurl()->execute();

            $split = new Model\Cart\Split($cartSplitQuery->getResult());
            $split->region = new Model\Region(['id' => $regionId]);

            foreach ($split->errors as $error) {
                throw new \Exception($error->message, $error->code);
            }

            // пользователь
            if ($userItemQuery) {
                try {
                    $user = (new \EnterRepository\User())->getObjectByQuery($userItemQuery);
                    $split->user->id = $user->id;
                    $split->user->ui = $user->ui;
                } catch (\Exception $e) {
                    $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['critical', 'order', 'slot']]);
                }
            }

            // обновление email и телефона
            if (!empty($request->data['email'])) {
                $split->user->email = (string)$request->data['email'];
            }
            if (!empty($phone)) {
                $split->user->phone = $phone;
            }
            if (!empty($request->data['name'])) {
                $split->user->firstName = (string)$request->data['name'];
            }

            $createOrderQuery = $this->prepareOrderCreatePacketQuery($split, $request->data['productId'], $regionId);
            $this->getCurl()->query($createOrderQuery);
            $orderCreatePacketResponse = $createOrderQuery->getResult();

            if (!isset($orderCreatePacketResponse[0]['number_erp'])) {
                throw new \Exception('Ошибка при создании заявки');
            }

            return new Http\JsonResponse([
                'orderNumber' => $orderCreatePacketResponse[0]['number_erp'],
            ]);
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'queries' => [$userItemQuery, $cartSplitQuery, $createOrderQuery], 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['critical', 'order', 'slot']]);

            $displayErrorCodes = [
                0,
                736, // Не указан телефон
                759, // Некорректный email
                720, // Это дублирующий заказ
                708, // Запрошенного количества товара нет в наличии
                752, // Товара нет в наличии на складе
                800, // Товар недоступен для продажи
            ];

            if (in_array($e->getCode(), $displayErrorCodes) || $this->getConfig()->debugLevel) {
                return new Http\JsonResponse(['error' => ['code' => $e->getCode(), 'message' => $e->getMessage()]], Http\Response::STATUS_INTERNAL_SERVER_ERROR);
            } else {
                return new Http\JsonResponse(['error' => ['code' => 0, 'message' => 'Ошибка при создании заявки']], Http\Response::STATUS_INTERNAL_SERVER_ERROR);
            }
        }
    }

    /**
     * @param string $userToken
     * @return Query\User\GetItemByToken|null
     */
    private function prepareUserItemQuery($userToken) {
        if ($userToken) {
            $userItemQuery =  new Query\User\GetItemByToken($userToken);
            $this->getCurl()->prepare($userItemQuery);
            return $userItemQuery;
        }

        return null;
    }

    /**
     * @param int $productId
     * @param int $regionId
     * @return Query\Cart\Split\GetItem
     */
    private function prepareCartSplitQuery($productId, $regionId) {
        $query = new Query\Cart\Split\GetItem(
            new Model\Cart([
                'product_list' => [
                    ['id' => $productId, 'quantity' => 1]
                ]
            ]),
            new Model\Region(['id' => $regionId])
        );
        $query->setTimeout(3 * $this->getConfig()->coreService->timeout);
        $this->getCurl()->prepare($query);

        return $query;
    }

    private function prepareOrderCreatePacketQuery(Model\Cart\Split $cartSplitResponse, $productId, $regionId) {
        $createOrderQuery = new Query\Order\CreatePacketBySplit($cartSplitResponse, $this->getOrderCreatePacketMetas($cartSplitResponse, $productId, $regionId), false, Model\Order::TYPE_SLOT);
        $createOrderQuery->setTimeout(90);

        return $createOrderQuery;
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