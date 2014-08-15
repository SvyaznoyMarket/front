<?php

namespace EnterRepository;

use Enter\Http;
use Enter\Util;
use EnterAggregator\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterModel as Model;
use EnterQuery as Query;

class Order {
    use ConfigTrait, LoggerTrait;

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

        if (!(bool)$errors) {
            $errors[] = ['code' => $error->getCode(), 'message' => 'Невозможно создать заказ'];
        }

        return $errors;
    }

    /**
     * @param \Enter\Curl\Query $query
     * @return Model\Order|null
     */
    public function getObjectByQuery(\Enter\Curl\Query $query) {
        $order = null;

        if ($item = $query->getResult()) {
            $order = new Model\Order($item);
        }

        return $order;
    }

    /**
     * @param Model\Order[] $orders
     */
    public function setDeliveryTypeForObjectList(array $orders) {
        try {
            $deliveryTypeData = Util\Json::toArray(file_get_contents($this->getConfig()->dir . '/data/query/delivery-type.json'));
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['repository']]);
        }

        foreach ($orders as $order) {
            foreach ($order->deliveries as $delivery) {
                $delivery->type = isset($deliveryTypeData[$delivery->typeId]) ? new Model\DeliveryType($deliveryTypeData[$delivery->typeId]) : null;
            }
        }
    }
}