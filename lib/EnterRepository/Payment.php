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
            (isset($result['detail']) && is_array($result['detail']) && !empty($result['url']))
            ? new Model\Payment\PsbForm(['url' => $result['url']] + $result['detail'])
            : null
        ;

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
            (isset($result['detail']) && is_array($result['detail']) && !empty($result['url']))
            ? new Model\Payment\PsbInvoiceForm(['url' => $result['url']] + $result['detail'])
            : null
        ;

        return $form;
    }

    /**
     * @param Query $query
     * @return array
     * @throws \Exception
     */
    public function getFormByQuery(Query $query) {
        $result = $query->getResult();

        $url = isset($result['url']) ? $result['url'] : $result['detail']['url'];
        if (isset($result['detail']['url'])) {
            unset($result['detail']['url']);
        }

        $fields = [];
        foreach ($result['detail'] as $k => $v) {
            $fields[] = [
                'name'  => $k,
                'value' => $v,
            ];
        }

        return [
            'url'    => $url,
            'fields' => $fields,
        ];
    }
}