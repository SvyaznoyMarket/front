<?php

namespace EnterQuery\Product\Category;

use Enter\Curl\Query;
use EnterQuery\ScmsQueryTrait;
use EnterQuery\Url;
use EnterModel as Model;

class GetTree extends Query {
    use ScmsQueryTrait;

    /** @var array */
    protected $result;

    /**
     * @param array $rootCriteria критерий категории, относительно которой будут загружатся предки, потомки и соседи
     * @param int|null $depth
     * @param bool|null $loadParents
     * @param bool|null $loadSibling
     * @param bool|null $loadMedia
     * @param string[] $mediaTypes
     */
    public function __construct(
        array $rootCriteria = null,
        $depth = null,
        $loadParents = null,
        $loadSibling = null,
        $loadMedia = null,
        array $mediaTypes = []
    ) {
        $this->url = new Url();
        $this->url->path = 'api/category/tree';

        // критерий для корневой категории
        if (isset($rootCriteria['token'])) {
            $this->url->query['root_slug'] = $rootCriteria['token'];
        }
        if (isset($rootCriteria['id'])) {
            $this->url->query['root_id'] = $rootCriteria['id'];
        }
        if (isset($rootCriteria['ui'])) {
            $this->url->query['root_uid'] = $rootCriteria['ui'];
        }
        // загружать предков относительно корневой категории
        if ($loadParents) {
            $this->url->query['load_parents'] = true;
        }
        // загружать соседей относительно корневой категории
        if ($loadSibling) {
            $this->url->query['load_siblings'] = true;
        }
        // глубина загрузки потомков относительно корневой категории
        if ($depth) {
            $this->url->query['depth'] = $depth;
        }
        // media
        if ($loadMedia) {
            $this->url->query['load_medias'] = true;
        }
        // тип media
        if ((bool)$mediaTypes) {
            $this->url->query['media_types'] = $mediaTypes;
        }

        $this->init();
    }

    /**
     * @param $response
     */
    public function callback($response) {
        $data = $this->parse($response);

        $this->result = isset($data['result'][0]['uid']) ? $data['result'] : [];
    }
}