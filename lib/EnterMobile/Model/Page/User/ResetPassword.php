<?php

namespace EnterMobile\Model\Page\User {
    use EnterMobile\Model\Page;

    class ResetPassword extends Page\DefaultPage {
        /** @var ResetPassword\Content */
        public $content;

        public function __construct() {
            parent::__construct();

            $this->content = new ResetPassword\Content();
        }
    }
}

namespace EnterMobile\Model\Page\User\ResetPassword {
    use EnterMobile\Model\Page;
    use EnterMobile\Model\Partial;

    class Content extends Page\DefaultPage\Content {
        /** @var \EnterModel\Message[] */
        public $messages = [];
        /** @var \EnterMobile\Model\Form\User\AuthForm|null */
        public $resetPasswordForm;
        /** @var string */
        public $redirectUrl;

        public function __construct() {
            parent::__construct();
        }
    }
}

