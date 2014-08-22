<?php

namespace EnterModel\Product;

use EnterModel as Model;

class Slice {
    /** @var string */
    public $token;
    /** @var string */
    public $name;
    /** @var string */
    public $filterQuery;
    /** @var array */
    public $filters = [];
    /** @var string */
    public $title;
    /** @var string */
    public $metaKeywords;
    /** @var string */
    public $metaDescription;
    /** @var array */
    public $description;
    /** @var string */
    public $categoryId;
    /** @var string */
    public $content;

    /**
     * @param array $data
     */
    public function __construct(array $data = []) {
        if (array_key_exists('url', $data)) $this->token = (string)$data['url'];
        if (array_key_exists('name', $data)) $this->name = (string)$data['name'];
        if (array_key_exists('title', $data)) $this->title = (string)$data['title'];
        if (array_key_exists('meta_keywords', $data)) $this->metaKeywords = (string)$data['meta_keywords'];
        if (array_key_exists('meta_description', $data)) $this->metaDescription = (string)$data['meta_description'];
        if (array_key_exists('description', $data)) $this->description = (string)$data['description'];
        if (array_key_exists('category_id', $data)) $this->categoryId = (string)$data['category_id'];
        if (array_key_exists('content', $data)) $this->content = (string)$data['content'];

        if (array_key_exists('filter', $data)) $this->filterQuery = (string)$data['filter'];

        try {
            parse_str($this->filterQuery, $this->filters);
            if ($this->categoryId) {
                $this->filters['category'] = $this->categoryId;
            }
        } catch (\Exception $e) {}
    }
}