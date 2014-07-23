<?php

namespace EnterSite\Repository\Page\User\Login;

use Enter\Http;
use EnterSite\Model;
use EnterSite\Repository;

class Request extends Repository\Page\DefaultLayout\Request {
    /** @var string */
    public $redirectUrl;
    /** @var string[] */
    public $authFormErrors = [];
    /** @var string[] */
    public $resetFormErrors = [];
    /** @var string[] */
    public $registerFormErrors = [];
    /** @var \EnterModel\Message[] */
    public $messages = [];
}