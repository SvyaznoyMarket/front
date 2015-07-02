<?php

namespace EnterMobile\Model\Page\Order {
    use EnterMobile\Model\Page;

    class Index extends Page\DefaultPage {
        /** @var Index\Content */
        public $content;
        /** @var array */
        public $steps = [];

        public function __construct() {
            parent::__construct();

            $this->content = new Index\Content();
        }
    }
}

namespace EnterMobile\Model\Page\Order\Index {
    use EnterMobile\Model\Page;
    use EnterMobile\Model\Partial;
    use EnterMobile\Model\Form;

    class Content extends Page\DefaultPage\Content {
        /** @var array */
        public $errors = [];
        /** @var Form\Order\UserForm */
        public $form;
        /** @var bool */
        public $isUserAuthenticated; // TODO: перенести на уровень выше
        /** @var string */
        public $authUrl;
        /** @var bool */
        public $hasMnogoRu;

        public function __construct() {
            parent::__construct();

            $this->form = new Form\Order\UserForm();
        }
    }
}
