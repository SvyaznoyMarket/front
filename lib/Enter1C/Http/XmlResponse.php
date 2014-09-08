<?php

namespace Enter1C\Http;

use Enter\Http;
use SimpleXMLElement;

class XmlResponse extends Http\Response {
    /** @var \ArrayObject */
    public $data;

    public function __construct($data = null, $statusCode = self::STATUS_OK) {
        parent::__construct(null, $statusCode);

        $this->data = new \ArrayObject((array)$data ? : []);

        $this->headers['Content-Type'] = 'text/xml; charset=' . ($this->charset ?: 'UTF-8');
    }

    /**
     * @return $this
     */
    public function sendContent() {
        $xml = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><result />");
        $this->arrayToXml($xml, json_decode(json_encode($this->data->getArrayCopy()), true));

        $this->content = $xml->asXML();

        return parent::sendContent();
    }

    /**
     * @param SimpleXMLElement $xml
     * @param $data
     * @param string|null $parentKey
     */
    private function arrayToXml(SimpleXMLElement &$xml, $data, $parentKey = null) {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (is_numeric($key)) {
                    $this->arrayToXml($xml, $value, $parentKey);
                } else {
                    $node = $xml->addChild($key);
                    $this->arrayToXml($node, $value, $key);
                }
            } else {
                //$value = htmlentities($value);
                $xml->addChild($key, $value);
            }
        }
    }
}