<?php
namespace EnterModel\Cart\Split;

use EnterMobileApplication\ConfigTrait;

class PaymentMethod {
    use ConfigTrait;

    /** @var string|null */
    public $id;
    /** @var string|null */
    public $ui;
    /** @var string|null */
    public $name;
    /** @var string|null */
    public $description;
    /** @var bool */
    public $isOnline;
    /** @var \EnterModel\MediaList */
    public $media;

    /**
     * @param array $data
     */
    public function __construct($data = []) {
        $this->id = $data['id'] ? (string)$data['id'] : null;
        $this->ui = $data['ui'] ? (string)$data['ui'] : null;
        $this->name = $data['name'] ? (string)$data['name'] : null;
        $this->description = $data['description'] ? (string)$data['description'] : null;
        $this->isOnline = (bool)$data['is_online'];
        $this->media = (new \EnterRepository\Media())->getMediaListForPaymentMethod($this->id, $this->isOnline, $this->getConfig());
    }

    /**
     * @return array
     */
    public function dump() {
        return [
            'id'          => $this->id,
            'ui'          => $this->ui,
            'name'        => $this->name,
            'description' => $this->description,
            'is_online'   => $this->isOnline,
        ];
    }
}
