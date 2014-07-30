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
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'action' => __METHOD__, 'tag' => ['repository']]);
        }

        foreach ($data as $item) {
            if (!isset($item['payment_methods'][0])) continue;

            foreach ($item['payment_methods'] as $methodItem) {
                if (isset($methodItem['id']) && (Model\PaymentMethod::CREDIT_ID === (string)$methodItem['id'])) {
                    return true;
                }
            }
        }

        return false;
    }
}