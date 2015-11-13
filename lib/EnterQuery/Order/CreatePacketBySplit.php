<?php

namespace EnterQuery\Order;

use Enter\Curl\Query;
use EnterAggregator\LoggerTrait;
use EnterQuery\CoreQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class CreatePacketBySplit extends Query {
    use CoreQueryTrait, LoggerTrait;

    /** @var array */
    protected $result;

    /**
     * @param Model\Cart\Split $split
     * @param Model\Order\Meta[] $metas
     * @param bool $isReceiveSms
     * @param int $typeId
     */
    public function __construct(
        Model\Cart\Split $split,
        array $metas = [],
        $isReceiveSms = false,
        $typeId = Model\Order::TYPE_ORDER
    ) {
        $this->retry = 1;

        $this->url = new Url();
        $this->url->path = 'v2/order/create-packet2';

        // build data
        $address = $split->user ? $split->user->address : null;

        $data = [];
        foreach ($split->orders as $order) {
            $delivery = $order->delivery;

            // дата доставки
            $deliveryDate = null;
            try {
                if ($delivery && $delivery->date) {
                    $deliveryDate = (new \DateTime())->setTimestamp($delivery->date);
                }
            } catch (\Exception $e) {
                $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['critical', 'repository']]);
            }

            $orderData = [
                'type_id'             => $typeId,
                'geo_id'              => $split->region->id,
                'user_id'             => $split->user ? $split->user->id : null,
                'is_legal'            => false, // FIXME!!!
                'payment_id'          => $order->paymentMethodId,
                'credit_bank_id'      => null, // FIXME!!!
                'last_name'           => $split->user ? $split->user->lastName : null,
                'first_name'          => $split->user ? $split->user->firstName : null,
                'email'               => ($split->user && !empty($split->user->email)) ? $split->user->email : null,
                'mobile'              => ($split->user && !empty($split->user->phone)) ? preg_replace('/[^\d]/', '', preg_replace('/^\+7/', '8', $split->user->phone)) : null,
                'address_street'      => null,
                'address_number'      => null,
                'address_building'    => null,
                'address_apartment'   => null,
                'address_floor'       => null,
                'shop_id'             => ($delivery && $delivery->point) ? $delivery->point->id : null,
                'point_ui'            => ($delivery && $delivery->point) ? $delivery->point->ui : null,
                'extra'               => $order->comment,
                'bonus_card_number'   => ($split->user && $split->user->bonusCardNumber) ? $split->user->bonusCardNumber : null,
                'delivery_type_id'    => $delivery ? $delivery->modeId : null, // ATTENTION
                'delivery_type_token' => $delivery ? $delivery->methodToken : null,
                'delivery_price'      => $delivery ? $delivery->price : null,
                'delivery_period'     => ($delivery && $delivery->interval) ? [$delivery->interval->from, $delivery->interval->to] : null,
                'delivery_date'       => $deliveryDate ? $deliveryDate->format('Y-m-d') : null,
                'ip'                  => $split->clientIp,
                'product'             => [],
                'is_online_payment_available' => $order->isOnlinePaymentAvailable,
            ];

            if ($order->delivery && $order->delivery->boxUi) {
                $orderData['box_ui'] = $order->delivery->boxUi;
            }

            if ($split->user->smsCode) {
                $orderData['sms_code'] = $split->user->smsCode;
            }

            if ($isReceiveSms) {
                $orderData['is_receive_sms'] = 1;
            }

            $orderData['subway_id'] = null; // FIXME!!!

            // адрес
            if ($address) {
                $orderData['address_street'] = $address->street;
                $orderData['address_number'] = $address->number;
                $orderData['address_building'] = $address->building;
                $orderData['address_apartment'] = $address->apartment;
                $orderData['address_floor'] = $address->floor;
            }

            // товары
            foreach ($order->products as $product) {
                $orderData['product'][] = [
                    'id'       => $product->id,
                    'quantity' => $product->quantity,
                ];
            }

            // action
            if ((bool)$order->actions) {
                $orderData['action'] = $order->actions;
            }

            // meta
            foreach ($metas as $meta) {
                $orderData['meta_data'][$meta->key] = $meta->value;
            }

            $data[] = $orderData;
        }

        $this->data = $data;

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data[0]) ? $data : [];
    }
}