<?php

namespace EnterMobile\Repository\Page\User\EditProfile;

use Enter\Http;
use EnterMobile\Model;
use EnterMobile\Repository;

class Request extends Repository\Page\User\DefaultPage\Request {
    /** @var string */
    public $redirectUrl;
    /** @var string[] */
    public $formErrors = [];
    /** @var \EnterModel\Message[] */
    public $messages = [];
    /** @var string[] */
    public $formFields = [];
}