<?php

namespace EnterMobile\Controller\Order\Slot;

use Enter\Http;
use EnterMobileApplication\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\CurlTrait;
use EnterMobileApplication\Repository;
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
        $curl = $this->getCurl();
        $productRepository = new \EnterRepository\Product();
        $userItemQuery = null;
        $cartSplitQuery = null;
        $createOrderQuery = null;

        try {
            $phone = $this->getValidatedPhone($request->data['phone']);

            if ($request->data['confirm'] != '1') {
                throw new \Exception('Не подтверждено согласие с офертой');
            }

            $userItemQuery = (new \EnterMobile\Repository\User())->getQueryByHttpRequest($request);
            if ($userItemQuery) {
                $curl->prepare($userItemQuery);
            }

            $cartSplitQuery = $this->prepareCartSplitQuery($request->data['productId'], $regionId);

            $curl->execute();

            $split = new Model\Cart\Split($cartSplitQuery->getResult());
            $split->region = new Model\Region(['id' => $regionId]);

            foreach ($split->errors as $error) {
                throw new \Exception($error->message, $error->code);
            }

            // пользователь
            $user = (new \EnterMobile\Repository\User())->getObjectByQuery($userItemQuery);
            if ($user) {
                $split->user->id = $user->id;
                $split->user->ui = $user->ui;
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
            $curl->query($createOrderQuery);
            $orderCreatePacketResponse = $createOrderQuery->getResult();

            if (!isset($orderCreatePacketResponse[0]['number_erp'])) {
                throw new \Exception('Ошибка при создании заявки');
            }

            $productIds = [];
            foreach ($orderCreatePacketResponse[0]['product'] as $product) {
                $productIds[] = $product['id'];
            }

            $curl = $this->getCurl();

            $productQuery = new Query\Product\GetListByIdList($productIds, $regionId, ['model' => false, 'related' => false]);
            $curl->prepare($productQuery);

            $descriptionListQuery = new Query\Product\GetDescriptionListByIdList(
                $productIds,
                [
                    'category' => true,
                    'label'    => true,
                    'brand'    => true,
                ]
            );
            $curl->prepare($descriptionListQuery);

            $curl->execute();

            $products = $productRepository->getIndexedObjectListByQueryList([$productQuery], [$descriptionListQuery]);

            return $request->isXmlHttpRequest() ? new Http\JsonResponse([
                'order' => [
                    'number' => $orderCreatePacketResponse[0]['number_erp'],
                    'isPartner' => $orderCreatePacketResponse[0]['is_partner'],
                    'paySum' => $orderCreatePacketResponse[0]['pay_sum'],
                    'delivery' => [
                        'price' => isset($orderCreatePacketResponse[0]['delivery'][0]) ? $orderCreatePacketResponse[0]['delivery'][0]['price'] : '',
                    ],
                    'region' => [
                        'name' => $orderCreatePacketResponse[0]['geo']['name'],
                    ],
                    'products' => array_map(function($product) use(&$products, $productRepository) {
                        $coreProduct = $productRepository->getObjectFromListById($products, $product['id']);
                        if (!$coreProduct) {
                            return [];
                        }

                        return [
                            'id' => $coreProduct->id,
                            'name' => $coreProduct->name,
                            'article' => $coreProduct->article,
                            'categories' => $coreProduct->category ? call_user_func($self = function(Model\Product\Category $category) use (&$self) {
                                return array_merge($category->parent ? $self($category->parent) : [], [['name' => $category->name]]);
                            }, $coreProduct->category) : [],
                            'price' => $product['price'],
                            'quantity' => $product['quantity'],
                        ];
                    }, $orderCreatePacketResponse[0]['product']),
                ],
            ]) : (new \EnterAggregator\Controller\Redirect())->execute($referer, 302);
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['critical', 'order', 'slot']]);

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
                return $request->isXmlHttpRequest() ? new Http\JsonResponse(['error' => $e->getMessage()]) : (new \EnterAggregator\Controller\Redirect())->execute($referer, 302);
            } else {
                return $request->isXmlHttpRequest() ? new Http\JsonResponse(['error' => 'Ошибка при создании заявки']) : (new \EnterAggregator\Controller\Redirect())->execute($referer, 302);
            }
        }
    }

    private function getValidatedPhone($phone) {
        if (empty($phone)) {
            throw new \Exception('Не указан телефон');
        }

        $phone = preg_replace('/^\+7/', '8', $phone);
        $phone = preg_replace('/[^\d]/', '', $phone);

        if (11 != strlen($phone)) {
            throw new \Exception('Неверный номер телефона');
        }

        return $phone;
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
        $query->setTimeout($this->getConfig()->coreService->timeout * 2);
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