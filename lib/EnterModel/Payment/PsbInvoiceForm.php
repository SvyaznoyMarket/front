<?php

namespace EnterModel\Payment;

class PsbInvoiceForm {
    /** @var string */
    public $url;
    /** @var string */
    public $contractorId;
    /** @var string */
    public $invoiceId;
    /** @var string */
    public $sum;
    /** @var string */
    public $payDescription;
    /** @var string */
    public $additionalInfo;
    /** @var string */
    public $signature;

    /**
     * @param array $data
     */
    public function __construct(array $data) {
        if (array_key_exists('url', $data)) $this->url = (string)$data['url'];
        if (array_key_exists('ContractorID', $data)) $this->contractorId = (string)$data['ContractorID'];
        if (array_key_exists('InvoiceID', $data)) $this->invoiceId = (string)$data['InvoiceID'];
        if (array_key_exists('Sum', $data)) $this->sum = (string)$data['Sum'];
        if (array_key_exists('PayDescription', $data)) $this->payDescription = (string)$data['PayDescription'];
        if (array_key_exists('AdditionalInfo', $data)) $this->additionalInfo = (string)$data['AdditionalInfo'];
        if (array_key_exists('Signature', $data)) $this->signature = (string)$data['Signature'];
    }
}