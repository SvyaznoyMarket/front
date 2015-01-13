<?php

namespace Enter1C;

trait XmlTrait {
    /**
     * Узлы с двумя или более дочерними элементами с одинаковыми именами преобразуются в массив с числовыми индексами.
     * Пустые элементы преобразуются в пустую строку (в противоположность способу
     * json_decode(json_encode(simplexml_load_string($xml)), true)).
     */
    public function convertXmlToArray($xml) {
        if (is_string($xml)) {
            $xml = simplexml_load_string($xml);
        }

        $count = count($xml);
        if ($count) {
            $result = [];
            $i = 0;
            foreach ($xml as $key => $val) {
                if (array_key_exists($key, $result)) {
                    $result[] = $result[$key];
                    unset($result[$key]);
                    if ($i == $count - 1) {
                        $result[] = $this->convertXmlToArray($val);
                    } else {
                        $result[$key] = $this->convertXmlToArray($val);
                    }
                } else {
                    $result[$key] = $this->convertXmlToArray($val);
                }

                $i++;
            }

            return $result;
        } else {
            return (string) $xml;
        }
    }
}
