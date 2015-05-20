<?php

namespace EnterMobile\Model\Page\Order {
    use EnterMobile\Model\Page;

    class Index extends Page\DefaultPage {
        /** @var Index\Content */
        public $content;

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
        /** @var Form\Order\UserForm */
        public $form;
        /** @var bool */
        public $isUserAuthenticated;
        /** @var string */
        public $authUrl;

        public function __construct() {
            parent::__construct();

            $this->form = new Form\Order\UserForm();
        }
    }
}
