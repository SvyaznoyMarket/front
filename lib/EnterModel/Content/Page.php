<?php
namespace EnterModel\Content;

class Page {
    /** @var string */
    public $ui = '';
    /** @var string */
    public $token = '';
    /** @var bool */
    public $isAvailableByDirectLink = false;
    /** @var string */
    public $title = '';
    /** @var string */
    public $contentHtml = '';

    public function __construct($data = []) {
        if (isset($data['uid'])) $this->ui = (string)$data['uid'];
        if (isset($data['token'])) $this->token = (string)$data['token'];
        if (isset($data['available_by_direct_link'])) $this->isAvailableByDirectLink = (bool)$data['available_by_direct_link'];
        if (isset($data['title'])) $this->title = trim($data['title']);
        if (isset($data['content'])) $this->contentHtml = trim($data['content']);
    }
}