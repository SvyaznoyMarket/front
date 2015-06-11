<?php

namespace EnterRepository;

use Enter\Http;
use Enter\Curl\Query;
use EnterAggregator\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterModel as Model;

class Cart {
    use ConfigTrait, LoggerTrait;

    /**
     * @param Http\Request $request
     * @return Model\Cart\Product|null
     */
    public function getProductObjectByHttpRequest(Http\Request $request) {
        $cartProduct = null;

        $productItem = [
            'id'       => null,
            'quantity' => null,
        ];
        if (!empty($request->query['product']['id'])) {
            $productItem = array_merge($productItem, $request->query['product']);
        } else if (!empty($request->data['product']['id'])) {
            $productItem = array_merge($productItem, $request->data['product']);
        }

        if ($productItem['id']) {
            $cartProduct = new Model\Cart\Product();
            $cartProduct->id = (string)$productItem['id'];
            $cartProduct->quantity = (int)$productItem['quantity'];
        }

        return $cartProduct;
    }

    /**
     * @param Http\Request $request
     * @return Model\Cart\Product[]
     */
    public function getProductListByHttpRequest(Http\Request $request) {
        $cartProducts = [];

        foreach ((array)$request->data['product'] as $productItem) {
            $productItem = array_merge([
                'id'         => null,
                'quantity'   => null,
                'parentId'   => null,
            ], (array)$productItem);
            if (!$productItem['id']) continue;

            $cartProduct = new Model\Cart\Product();
            $cartProduct->id = (string)$productItem['id'];
            $cartProduct->quantity = (int)$productItem['quantity'];
            $cartProduct->parentId = (string)$productItem['parentId'];
            $cartProduct->quantitySign = (isset($productItem['quantitySign']) && in_array($productItem['quantitySign'], ['-', '+']))
                ? (string)$productItem['quantitySign']
                : null; // FIXME

            $cartProducts[] = $cartProduct;
        }

        return $cartProducts;
    }

    /**
     * @param Http\Request $request
     * @return Model\Cart\Product[]
     */
    public function getProductObjectListByHttpRequest(Http\Request $request) {
        $productData = (array)(is_array($request->query['products']) ? $request->query['products'] : $request->data['products']);

        $products = [];
        foreach ($productData as $productItem) {
            if (!isset($productItem['id'])) continue;

            $cartProduct = new Model\Cart\Product();
            $cartProduct->id = (string)$productItem['id'];
            $cartProduct->quantity = isset($productItem['quantity']) ? (int)$productItem['quantity'] : 0;
            $cartProduct->sender = isset($productItem['sender']['name']) ? (array)$productItem['sender'] : null;

            $products[] = $cartProduct;
        }

        return $products;
    }

    public function getPointImageUrl($pointToken) {
        switch ($pointToken) {
            case 'shops':
                $image = 'enter.png';
                break;
            case 'self_partner_pickpoint':
                $image = 'pickpoint.png';
                break;
            case 'self_partner_svyaznoy':
            case 'shops_svyaznoy':
                $image = 'svyaznoy.png';
                break;
            case 'self_partner_euroset':
                $image = 'euroset.png';
                break;
            case 'self_partner_hermes':
                $image = 'hermesdpd.png';
                break;
            default:
                $image = '';
                break;
        }

        if ($image) {
            $image = 'http://' . $this->getConfig()->hostname . '/' . $this->getConfig()->version . '/img/points/' . $image;
        }

        return $image;
    }

    /**
     * @param Http\Session $session
     * @return Model\Cart
     */
    public function getObjectByHttpSession(Http\Session $session) {
        $cart = new Model\Cart();

        $cartData = array_merge([
            'product' => [],
        ], (array)$session->get('cart'));

        // импорт старой корзины
        $oldCartData = array_merge([
            'productList' => [],
        ], (array)$session->get('userCart'));
        foreach ($oldCartData['productList'] as $productId => $productQuantity) {
            if (!isset($cartData['product'][$productId])) {
                $cartData['product'][$productId] = [
                    'id'       => $productId,
                    'quantity' => $productQuantity,
                ];
            }
        }

        foreach ($cartData['product'] as $productItem) {
            $productItem = array_merge([
                'id'       => null,
                'ui'       => null,
                'quantity' => null,
                'parentId' => null,
                'added'    => null,
            ], $productItem);

            $cartProduct = new Model\Cart\Product();
            $cartProduct->id = (string)$productItem['id'];
            $cartProduct->ui = (string)$productItem['ui'];
            $cartProduct->quantity = (int)$productItem['quantity'];
            $cartProduct->parentId = $productItem['parentId'] ? (string)$productItem['parentId'] : null;
            $cartProduct->addedAt = $productItem['added'] ? (string)$productItem['added'] : null;

            $cart->product[$cartProduct->id] = $cartProduct;
        }

        return $cart;
    }

    /**
     * @param Http\Session $session
     * @param Model\Cart $cart
     */
    public function saveObjectToHttpSession(Http\Session $session, Model\Cart $cart) {
        // TODO: купоны, ...

        // сохранение в старой корзине
        $oldCartData = [
            'productList' => [],
        ];

        foreach ($cart->product as $cartProduct) {
            $oldCartData['productList'][$cartProduct->id] = $cartProduct->quantity;
        }
        $session->set('userCart', $oldCartData);

        // новая корзина
        $cartData = [
            'product' => [],
        ];

        foreach ($cart->product as $cartProduct) {
            $cartItem = [
                'id'       => $cartProduct->id,
                'ui'       => $cartProduct->ui,
                'quantity' => $cartProduct->quantity,
            ];
            if ($cartProduct->parentId) {
                $cartItem['parentId'] = $cartProduct->parentId;
            }

            if (!empty($cartProduct->sender['name'])) {
                $cartItem['sender'] = $cartProduct->sender;
            }

            if (!isset($cartData['product'][$cartProduct->id]['added'])) {
                $cartItem['added'] = date('c');
            } else {
                $cartItem = array_merge($cartData['product'][$cartProduct->id], $cartItem);
            }

            $cartData['product'][$cartProduct->id] = $cartItem;
        }
        $session->set('cart', $cartData);
    }

    /**
     * @param \EnterModel\Cart $cart
     * @param Query $query
     */
    public function updateObjectByQuery(Model\Cart $cart, Query $query) {
        $cartProductsById = [];
        foreach ($cart->product as $cartProduct) {
            $cartProductsById[$cartProduct->id] = $cartProduct;
        }

        $item = $query->getResult();
        $coreCart = new Model\Cart($item);

        $cart->sum = $coreCart->sum;
        foreach ($coreCart->product as $coreCartProduct) {
            /** @var \EnterModel\Cart\Product|null $cartProduct */
            $cartProduct = isset($cartProductsById[$coreCartProduct->id]) ? $cartProductsById[$coreCartProduct->id] : null;
            if (!$cartProduct) continue;

            $cartProduct->price = $coreCartProduct->price;
            $cartProduct->sum = $coreCartProduct->sum;
            $cartProduct->quantity = $coreCartProduct->quantity;
        }
    }

    /**
     * @param Model\Cart $cart
     * @param Model\Cart\Product $cartProduct
     */
    public function setProductForObject(Model\Cart $cart, Model\Cart\Product $cartProduct) {
        if ($cartProduct->quantity <= 0) {
            if (isset($cart->product[$cartProduct->id])) unset($cart->product[$cartProduct->id]);
        } else {
            $cart->product[$cartProduct->id] = $cartProduct;
        }
    }

    /**
     * @param $id
     * @param Model\Cart $cart
     * @return Model\Cart\Product|null
     */
    public function getProductById($id, Model\Cart $cart) {
        $return = null;

        foreach ($cart->product as $cartProduct) {
            if ($cartProduct->id === $id) {
                $return = $cartProduct;

                break;
            }
        }

        return $return;
    }

    /**
     * @param $changeData
     * @param $previousSplitData
     * @return array
     */
    public function dumpSplitChange($changeData, $previousSplitData) {
        $dump = [];

        // заказ
        if (!empty($changeData['orders']) && is_array($changeData['orders'])) {
            foreach ($changeData['orders'] as $orderItem) {
                $blockName = isset($orderItem['blockName']) ? $orderItem['blockName'] : null;

                if (!$blockName || !isset($previousSplitData['orders'][$blockName])) {
                    $this->getLogger()->push(['type' => 'warn', 'message' => 'Передан несуществующий блок заказа', 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['order.split']]);
                    continue;
                }

                $dump['orders'][$blockName] = $previousSplitData['orders'][$blockName] + [
                        'products'  => [],
                        'discounts' => [],
                    ];

                // метод получения
                if (isset($orderItem['delivery']['methodToken'])) {
                    $dump['orders'][$blockName]['delivery'] = [
                        'delivery_method_token' => $orderItem['delivery']['methodToken'],
                    ];
                }

                // точка получения
                if (isset($orderItem['delivery']['point']['id']) && isset($orderItem['delivery']['point']['groupToken'])) {
                    $dump['orders'][$blockName]['delivery']['point'] = [
                        'id'    => $orderItem['delivery']['point']['id'],
                        'token' => $orderItem['delivery']['point']['groupToken'],

                    ];
                }

                // дата получения
                if (isset($orderItem['delivery']['date'])) {
                    $dump['orders'][$blockName]['delivery']['date'] = $orderItem['delivery']['date'];
                }

                // интервал
                if (isset($orderItem['delivery']['interval'])) {
                    $dump['orders'][$blockName]['delivery']['interval'] = $orderItem['delivery']['interval'];
                }

                // комментарий
                if (array_key_exists('comment', $orderItem)) {
                    $dump['orders'][$blockName]['comment'] = $orderItem['comment'];
                }

                // способ оплаты
                if (array_key_exists('paymentMethodId', $orderItem)) {
                    $dump['orders'][$blockName]['payment_method_id'] = $orderItem['paymentMethodId'];
                }

                // количество товаров
                if (isset($orderItem['products'][0])) {
                    $quantitiesByProductId = [];
                    foreach ($orderItem['products'] as $productItem) {
                        if (empty($productItem['id']) || !isset($productItem['quantity'])) {
                            $this->getLogger()->push(['type' => 'warn', 'message' => 'Не указан ид или не найден товар', 'product' => $productItem, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['order.split']]);
                            continue;
                        }

                        $quantitiesByProductId[$productItem['id']] = (int)$productItem['quantity'];
                    }

                    $productItem = null;
                    foreach ($dump['orders'][$blockName]['products'] as &$productItem) {
                        if (!isset($productItem['id']) || !isset($quantitiesByProductId[$productItem['id']])) {
                            $this->getLogger()->push(['type' => 'warn', 'message' => 'Не указан ид или не найден товар', 'product' => $productItem, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['order.split']]);
                            continue;
                        }

                        $productItem['quantity'] = $quantitiesByProductId[$productItem['id']];
                    }
                    unset($productItem);
                }

                // скидки
                if (isset($orderItem['discounts'][0])) {
                    $discountItem = null;
                    foreach ($orderItem['discounts'] as $discountItem) {
                        $this->getLogger()->push(['message' => 'Применение купона', 'discount' => $discountItem, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['order.split']]);

                        if (empty($discountItem['number'])) {
                            $this->getLogger()->push(['type' => 'warn', 'message' => 'Не передан номер купона', 'discount' => $discountItem, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['order.split']]);
                            continue;
                        }

                        if (isset($discountItem['delete']) && $discountItem['delete']) { // удаление купона
                            $isDeleted = false;
                            // поиск существующей скидки
                            foreach ($dump['orders'][$blockName]['discounts'] as $i => $existsDiscountItem) {
                                if ($existsDiscountItem['number'] == $discountItem['number']) {
                                    // удаление найденной скидки
                                    unset($dump['orders'][$blockName]['discounts'][$i]);
                                }
                            }
                            if (!$isDeleted) {
                                $this->getLogger()->push(['type' => 'warn', 'message' => 'Купон не найден', 'discount' => $discountItem, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['order.split']]);
                            }
                        } else { // добавление купона
                            $dump['orders'][$blockName]['discounts'][] = ['number' => $discountItem['number'], 'name' => null, 'type' => null, 'discount' => null];
                        }
                    }
                    unset($discountItem);
                }
            }
        }

        // инфо пользователя
        if (!empty($changeData['user'])) {
            $dump['user_info'] = $previousSplitData['user_info'];

            if (array_key_exists('phone', $changeData['user'])) {
                $dump['user_info']['phone'] = $changeData['user']['phone'];
            }
            if (array_key_exists('lastName', $changeData['user'])) {
                $dump['user_info']['last_name'] = $changeData['user']['lastName'];
            }
            if (array_key_exists('firstName', $changeData['user'])) {
                $dump['user_info']['first_name'] = $changeData['user']['firstName'];
            }
            if (array_key_exists('email', $changeData['user'])) {
                $dump['user_info']['email'] = !empty($changeData['user']['email']) ? $changeData['user']['email'] : null;
            }
            if (array_key_exists('bonusCardNumber', $changeData['user'])) {
                $dump['user_info']['bonus_card_number'] = $changeData['user']['bonusCardNumber'];
            }
            if (array_key_exists('address', $changeData['user'])) {
                if (array_key_exists('street', $changeData['user']['address'])) {
                    $dump['user_info']['address']['street'] = $changeData['user']['address']['street'];
                }
                if (array_key_exists('building', $changeData['user']['address'])) {
                    $dump['user_info']['address']['building'] = $changeData['user']['address']['building'];
                }
                if (array_key_exists('number', $changeData['user']['address'])) {
                    $dump['user_info']['address']['number'] = $changeData['user']['address']['number'];
                }
                if (array_key_exists('apartment', $changeData['user']['address'])) {
                    $dump['user_info']['address']['apartment'] = $changeData['user']['address']['apartment'];
                }
                if (array_key_exists('floor', $changeData['user']['address'])) {
                    $dump['user_info']['address']['floor'] = $changeData['user']['address']['floor'];
                }
                if (array_key_exists('subwayName', $changeData['user']['address'])) {
                    $dump['user_info']['address']['metro_station'] = $changeData['user']['address']['subwayName'];
                }
                if (array_key_exists('kladrId', $changeData['user']['address'])) {
                    $dump['user_info']['address']['kladr_id'] = $changeData['user']['address']['kladrId'];
                }
            }
        }

        return $dump;
    }
}