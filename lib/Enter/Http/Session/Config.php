<?php

namespace Enter\Http\Session;

class Config {
    /** @var string */
    public $name;
    /** @var string|null */
    public $id;
    /** @var int */
    public $cookieLifetime;
    /** @var string */
    public $cookieDomain;
    /** @var string */
    public $flashKey;
}