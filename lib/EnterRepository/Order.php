<?php

namespace EnterRepository;

use Enter\Http;
use EnterAggregator\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterModel as Model;
use EnterQuery as Query;

class Order {
    use ConfigTrait, LoggerTrait;

    /**
     * @param Model\Cart\Split $split
     * @return Query\Order\CreatePacket
     */
    public function getPacketQueryBySplit(Model\Cart\Split $split) {
        $logger = $this->getLogger();

        $query = null;

        $user = $split->user;
        $address = $user ? $user->address : null;

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
                'geo_id'              => $split->region->id,
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
                'shop_id'             => ($delivery && $delivery->point) ? $delivery->point->id : null,
                'extra'               => $order->comment,
                'bonus_card_number'   => null, // FIXME!!!
                'delivery_type_id'    => $delivery ? $delivery->modeId : null, // ATTENTION
                'delivery_type_token' => $delivery ? $delivery->methodToken : null,
                'delivery_price'      => $delivery ? $delivery->price : null,
                'delivery_period'     => ($delivery && $delivery->interval) ? [$delivery->interval->from, $delivery->interval->to] : null,
                'delivery_date'       => $deliveryDate ? $deliveryDate->format('Y-m-d') : null,
                'ip'                  => $split->clientIp,
                'product'             => [],
            ];

            $orderData['subway_id'] = null; // FIXME!!!

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

            $data[] = $orderData;
        }

        $query = new Query\Order\CreatePacket($data);

        return $query;
    }

    /**
     * @param Query\CoreQueryException $error
     * @return array
     */
    public function getErrorList(Query\CoreQueryException $error) {
        $errors = [];

        $messagesByCode = json_decode(file_get_contents($this->getConfig()->dir . '/data/core-error.json'), true);
        if (isset($messagesByCode[(string)$error->getCode()])) {
            $errors[] = ['code' => $error->getCode(), 'message' => $messagesByCode[(string)$error->getCode()]];
        }

        if (in_array($error->getCode(), [705, 708])) {

        }

        if (!(bool)$errors) {
            $errors[] = ['code' => $error->getCode(), 'message' => 'Невозможно создать заказ'];
        }

        return $errors;
    }

    public function getObjectByQuery(\Enter\Curl\Query $query) {
        $order = null;

        if ($item = $query->getResult()) {
            $order = new Model\Order($item);
        }

        return $order;
    }
}