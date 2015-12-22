<?php

namespace EnterMobile\Model\Page\User {

    class DefaultPage extends \EnterMobile\Model\Page\DefaultPage {

        public $content;

        public function __construct() {
            parent::__construct();

            $this->content = new DefaultPage\Content();
        }
    }
}

namespace EnterMobile\Model\Page\User\DefaultPage {

    class Content extends \EnterMobile\Model\Page\DefaultPage\Content{
        /** @var array */
        public $userMenu;
        /** @var array */
        public $user;

        public function __construct() {
            parent::__construct();
        }
    }

}