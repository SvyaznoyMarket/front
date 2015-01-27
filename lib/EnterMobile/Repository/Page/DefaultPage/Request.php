<?php

namespace EnterMobile\Repository\Page\DefaultPage;

use Enter\Http;
use EnterMobile\Model;

class Request {
    /** @var string|null */
    public $title;
    /** @var \EnterModel\Region */
    public $region;
    /** @var \EnterModel\MainMenu */
    public $mainMenu;
    /** @var Http\Request */
    public $httpRequest;
}