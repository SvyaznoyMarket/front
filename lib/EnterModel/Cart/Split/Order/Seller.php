<?php
namespace EnterModel\Cart\Split\Order;

class Seller {
    const UI_ENTER = '24594081-6c68-11e2-a300-e83935c0a4d4';
    const UI_SVYAZNOY = 'c562d9cb-cfd7-11e1-be71-3c4a92f6ffb8';
    const UI_SORDEX = '22cda64d-352a-11e5-93fc-288023e9c8ac';

    /** @var string|null */
    public $id;
    /** @var string|null */
    public $ui;
    /** @var string|null */
    public $name;
    /** @var string|null */
    public $offerUrl;

    /**
     * @param array $data
     */
    public function __construct($data = []) {
        $this->id = $data['id'] ? (string)$data['id'] : null;
        $this->ui = $data['ui'] ? (string)$data['ui'] : null;
        $this->name = $data['name'] ? (string)$data['name'] : null;
        $this->offerUrl = !empty($data['offer']) ? (string)$data['offer'] : null;
    }

    /**
     * @return array
     */
    public function dump() {
        return [
            'id'    => $this->id,
            'ui'    => $this->ui,
            'name'  => $this->name,
            'offer' => $this->offerUrl,
        ];
    }
}
