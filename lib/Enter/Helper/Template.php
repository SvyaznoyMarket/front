<?php

namespace Enter\Helper;

class Template {
    /**
     * @param $value
     * @return string
     */
    public function escape($value) {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * @param $value
     * @return string
     */
    public function unescape($value) {
        return htmlspecialchars_decode($value, ENT_QUOTES);
    }

    /**
     * @param $value
     * @return string
     */
    public function json($value) {
        return htmlspecialchars(json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
    }
}