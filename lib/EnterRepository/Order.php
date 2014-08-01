<?php

namespace EnterRepository;

use Enter\Http;
use EnterAggregator\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterModel as Model;
use EnterQuery as Query;

class Order {
    use ConfigTrait, LoggerTrait;

    public function getPacketQueryBySplit(Model\Cart\Split $split) {
        $logger = $this->getLogger();

        $query = null;

        $user = $split->user;

        $data = [];
        foreach ($split->orders as $order) {
            $delivery = $order->delivery;

            $deliveryDate = null;
            try {
                if ($delivery && $delivery->date) {
                    $deliveryDate = (new \DateTime())->setTimestamp($delivery->date);
                }
            } catch (\Exception $e) {
                $logger->push(['type' => 'error', 'error' => $e, 'action' => __METHOD__, 'tag' => ['critical', 'repository']]);
            }

            $orderData = [
                'type_id'             => 1, // TODO: вынести в константу
                'geo_id'              => 14975, // FIXME!!!
                'user_id'             => null, // FIXME!!!
                'is_legal'            => false, // FIXME!!!
                'payment_id'          => $order->paymentMethodId,
                'credit_bank_id'      => null, // FIXME!!!
                'last_name'           => $user ? $user->lastName : null,
                'first_name'          => $user ? $user->firstName : null,
                'email'               => $user ? $user->email : null,
                'mobile'              => $user ? $user->phone : null,
                'address_street'      => null,
                'address_number'      => null,
                'address_building'    => null,
                'address_apartment'   => null,
                'address_floor'       => null,
                'extra'               => null, // FIXME!!! добавить $order->comment
                'bonus_card_number'   => null, // FIXME!!!
                'delivery_type_id'    => null, // FIXME!!!
                'delivery_type_token' => $delivery ? $delivery->methodToken : null,
                'delivery_price'      => $delivery ? $delivery->price : null,
                'delivery_period'     => ($delivery && $delivery->interval) ? [$delivery->interval->from, $delivery->interval->to] : null,
                'delivery_date'       => $deliveryDate ? $deliveryDate->format('Y-m-d') : null,
                'ip'                  => $split->clientIp,
                'product'             => [],
                'service'             => [],
                'payment_params'      => [
                    'qiwi_phone' => null, // FIXME!!!
                ],
            ];

            $data[] = $orderData;
        }

        $query = new Query\Order\CreatePacket($data);

        return $query;
    }
}