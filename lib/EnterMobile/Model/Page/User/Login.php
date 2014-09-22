<?php

namespace EnterMobile\Model\Page\User {
    use EnterMobile\Model\Page;

    class Login extends Page\DefaultPage {
        /** @var Login\Content */
        public $content;

        public function __construct() {
            parent::__construct();

            $this->content = new Login\Content();
        }
    }
}

namespace EnterMobile\Model\Page\User\Login {
    use EnterMobile\Model\Page;
    use EnterMobile\Model\Partial;

    class Content extends Page\DefaultPage\Content {
        /** @var \EnterModel\Message[] */
        public $messages = [];
        /** @var string */
        public $formState;
        /** @var \EnterMobile\Model\Form\User\AuthForm|null */
        public $authForm;
        /** @var \EnterMobile\Model\Form\User\ResetForm|null */
        public $resetForm;
        /** @var \EnterMobile\Model\Form\User\RegisterForm|null */
        public $registerForm;
        /** @var string */
        public $redirectUrl;
        /** @var string */
        public $facebookLoginUrl;
        /** @var string */
        public $vkontakteLoginUrl;

        public function __construct() {
            parent::__construct();
        }
    }
}

