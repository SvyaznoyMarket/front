<?php

namespace EnterMobile\Model\Page\User {
    use EnterMobile\Model\Page;

    class EditProfile extends Page\DefaultPage {
        /** @var EditProfile\Content */
        public $content;

        public function __construct() {
            parent::__construct();

            $this->content = new EditProfile\Content();
        }
    }
}

namespace EnterMobile\Model\Page\User\EditProfile {
    use EnterMobile\Model\Page;
    use EnterMobile\Model\Partial;

    class Content extends Page\DefaultPage\Content {
        /** @var \EnterModel\Message[] */
        public $messages = [];
        /** @var \EnterMobile\Model\Form\User\EditProfileForm|null */
        public $editProfileForm;
        /** @var string */
        public $redirectUrl;

        public function __construct() {
            parent::__construct();
        }
    }
}

