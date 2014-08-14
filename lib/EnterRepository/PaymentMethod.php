<?php

namespace EnterRepository;

use Enter\Curl\Query;
use EnterAggregator\LoggerTrait;
use EnterModel as Model;

class PaymentMethod {
    use LoggerTrait;

    /**
     * @param Query $query
     * @return Model\PaymentMethod[]
     */
    public function getIndexedObjectListByQuery(Query $query) {
        $paymentMethods = [];

        $data = [];
        try {
            $data = $query->getResult();
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'action' => __METHOD__, 'tag' => ['repository']]);
        }

        $paymentGroupData = isset($data['groups']) ? (array)$data['groups'] : [];
        $paymentMethodData = isset($data['methods']) ? (array)$data['methods'] : [];

        foreach ($paymentMethodData as $paymentMethodItem) {
            if (!isset($paymentMethodItem['id'])) continue;

            $paymentMethod = new Model\PaymentMethod($paymentMethodItem);

            $paymentGroupItem = isset($paymentGroupData[$paymentMethod->groupId]['id']) ? $paymentGroupData[$paymentMethod->groupId] : null;
            if ($paymentGroupItem) {
                $paymentMethod->group = new Model\PaymentGroup($paymentGroupItem);
            }

            $paymentMethods[$paymentMethod->id] = $paymentMethod;
        }

        return $paymentMethods;
    }
}