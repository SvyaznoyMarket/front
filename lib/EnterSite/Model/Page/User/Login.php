<?php

namespace EnterSite\Model\Page\User {
    use EnterSite\Model\Page;

    class Login extends Page\DefaultLayout {
        /** @var Login\Content */
        public $content;

        public function __construct() {
            parent::__construct();

            $this->content = new Login\Content();
        }
    }
}

namespace EnterSite\Model\Page\User\Login {
    use EnterSite\Model\Page;
    use EnterSite\Model\Partial;

    class Content extends Page\DefaultLayout\Content {
        /** @var \EnterModel\Message[] */
        public $messages = [];
        /** @var \EnterSite\Model\Form\User\AuthForm */
        public $authForm;
        /** @var \EnterSite\Model\Form\User\RegisterForm|null */
        public $registerForm;
        /** @var string */
        public $redirectUrl;

        public function __construct() {
            parent::__construct();
        }
    }
}

