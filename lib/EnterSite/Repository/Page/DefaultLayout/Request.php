<?php

namespace EnterSite\Repository\Page\DefaultLayout;

use EnterSite\Model;

class Request {
    /** @var \EnterModel\Region */
    public $region;
    /** @var Model\MainMenu\Element[] */
    public $mainMenu = [];
}