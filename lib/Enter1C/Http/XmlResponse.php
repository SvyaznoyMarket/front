<?php

namespace Enter1C\Http;

use Enter\Http;
use SimpleXMLElement;

class XmlResponse extends Http\Response {
    /** @var \ArrayObject */
    public $data;

    public function __construct($data = null, $statusCode = self::STATUS_OK) {
        parent::__construct(null, $statusCode);

        $this->data = new \ArrayObject($data ? : []);

        $this->headers['Content-Type'] = 'text/xml';
    }

    /**
     * @return $this
     */
    public function sendContent() {
        $xml = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><response />");
        $this->arrayToXml($this->data, $xml);

        $this->content = $xml->asXML();

        return parent::sendContent();
    }

    /**
     * @param $data
     * @param SimpleXMLElement $xml
     */
    private function arrayToXml($data, SimpleXMLElement &$xml) {
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                $key = 'item';
            }

            $key = preg_replace('/[^a-z]/i', '', $key);

            if (is_array($value)) {
                $node = $xml->addChild($key);
                $this->arrayToXml($value, $node);
            } else {
                $value = htmlentities($value);
                $xml->addChild($key, $value);
            }
        }
    }
}