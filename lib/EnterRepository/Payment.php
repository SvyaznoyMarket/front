<?php

namespace EnterRepository;

use Enter\Curl\Query;
use EnterAggregator\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterModel as Model;

class Payment {
    use ConfigTrait, LoggerTrait;

    /**
     * @param Query $query
     * @return Model\Payment\PsbForm|null
     * @throws \Exception
     */
    public function getPsbFormByQuery(Query $query) {
        $result = $query->getResult();

        $form =
            (isset($result['detail']) && is_array($result['detail']))
            ? new Model\Payment\PsbForm($result['detail'])
            : null;

        return $form;
    }

    /**
     * @param Query $query
     * @return Model\Payment\PsbInvoiceForm|null
     * @throws \Exception
     */
    public function getPsbInvoiceFormByQuery(Query $query) {
        $result = $query->getResult();

        $form =
            (isset($result['detail']) && is_array($result['detail']))
            ? new Model\Payment\PsbInvoiceForm($result['detail'])
            : null;

        return $form;
    }
}