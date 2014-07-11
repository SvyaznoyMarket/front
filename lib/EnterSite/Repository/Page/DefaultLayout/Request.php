<?php

namespace EnterSite\Repository\Page\DefaultLayout;

use Enter\Http;
use EnterSite\Model;

class Request {
    /** @var \EnterModel\Region */
    public $region;
    /** @var Model\MainMenu */
    public $mainMenu;
    /** @var Http\Request */
    public $httpRequest;
}