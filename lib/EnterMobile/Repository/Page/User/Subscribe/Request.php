<?php
namespace EnterMobile\Repository\Page\User\Subscribe;

use EnterMobile\Model;
use EnterMobile\Repository;

class Request extends Repository\Page\User\DefaultPage\Request {
    /** @var array */
    public $subscriptionsGroupedByChannel = [];
    /** @var \EnterModel\Subscribe\Channel[] */
    public $channelsById = [];
}