<?php

namespace EnterMobile\Model\Page\Order {
    use EnterMobile\Model\Page;

    class Delivery extends Page\DefaultPage {
        /** @var Delivery\Content */
        public $content;

        public function __construct() {
            parent::__construct();

            $this->content = new Delivery\Content();
        }
    }
}

namespace EnterMobile\Model\Page\Order\Delivery {
    use EnterMobile\Model\Page;
    use EnterMobile\Model\Partial;
    use EnterMobile\Model\Form;

    class Content extends Page\DefaultPage\Content {
        /** @var Form\Order\DeliveryForm */
        public $form;
        /** @var array */
        public $deliveryForm = [
            'url' => null,
        ];
        /** @var array */
        public $region;
        /** @var array */
        public $orders = [];
        /** @var string|bool */
        public $orderCountMessage;
        /** @var string */
        public $kladrDataValue;
        /** @var bool */
        public $isUserAuthenticated; // TODO: перенести на уровень выше

        public function __construct() {
            parent::__construct();

            $this->form = new Form\Order\DeliveryForm();
        }
    }
}