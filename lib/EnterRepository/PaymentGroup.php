<?php

namespace EnterRepository;

use Enter\Curl\Query;
use EnterAggregator\LoggerTrait;
use EnterModel as Model;

class PaymentGroup {
    use LoggerTrait;

    /**
     * @param Query $query
     * @return bool
     */
    public function checkCreditObjectByListQuery(Query $query) {
        $data = [];
        try {
            $data = $query->getResult();
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['repository']]);
        }

        foreach ($data as $item) {
            if (!isset($item['payment_methods'][0])) continue;

            foreach ($item['payment_methods'] as $methodItem) {
                if (isset($methodItem['id']) && isset($methodItem['is_credit']) && (bool)$methodItem['is_credit']) {
                    return true;
                }
            }
        }

        return false;
    }
}