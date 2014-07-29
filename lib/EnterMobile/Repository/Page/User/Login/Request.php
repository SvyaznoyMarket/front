<?php

namespace EnterMobile\Repository\Page\User\Login;

use Enter\Http;
use EnterMobile\Model;
use EnterMobile\Repository;

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
    /** @var string[] */
    public $authFormFields = [];
    /** @var string[] */
    public $registerFormFields = [];
    /** @var string[] */
    public $resetFormFields = [];
}