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

        // MAPI-229
        if ($error->getCode() == 600 && $error->getMessage() === 'Неверно заполнены товары в заказе (нет ни одного с quantity > 0)') {
            $code = '10001';
        } else {
            $code = (string)$error->getCode();
        }

        if (isset($messagesByCode[$code])) {
            $errors[] = ['code' => (int)$code, 'message' => $messagesByCode[$code], 'detail' => $error->getDetail()];
        }

        if (!$errors) {
            $errors[] = ['code' => (int)$code, 'message' => 'Невозможно создать заказ', 'detail' => $error->getDetail()];
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
     * @param \Enter\Curl\Query $query
     * @return Model\Order[]
     */
    public function getObjectListByQuery(\Enter\Curl\Query $query) {
        $orders = [];

        foreach ($query->getResult() as $item) {
            if (!isset($item['number'])) continue;

            $orders[] = new Model\Order($item);
        }

        return $orders;
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

    /**
     * @param int|string|null $sum
     * @param Model\Region $region
     * @return int|null
     */
    public function getRemainSum($sum, Model\Region $region)
    {
        $ids = ['82', '83', '14974', '108136'];
        $minSum = $this->getConfig()->order->minSum;

        if (
            in_array($region->id, $ids)
            || ($region->parent && in_array($region->parent, $ids))
            || (null === $sum)
            || !$minSum
        ) {
            return null;
        }

        $diff = $minSum - $sum;

        $remainSum = ($diff < 0) ? 0 : $diff;

        if ($remainSum <= 1) {
            return null;
        }

        return $remainSum;
    }

    /**
     * @param Model\Region $region
     * @return int|null
     */
    public function getMinSum(Model\Region $region) {
        return in_array($region->id, ['82', '83', '14974', '108136']) ? 0 : $this->getConfig()->order->minSum;
    }
}