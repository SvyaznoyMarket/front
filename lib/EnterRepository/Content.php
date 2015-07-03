<?php

namespace EnterRepository;

class Content {
    /**
     * @param string $path
     * @return string|null
     */
    public function getTokenByPath($path) {
        $path = preg_replace('/^\/+|\/+$/s', '', $path);

        $segments = explode('/', $path);
        // TODO: добавить поддержку для путей из 4х сегментов
        if (count($segments) <= 3) {
            return trim(end($segments));
        }

        return null;
    }

    public function getProductBarcodesByPath($path) {
        $path = preg_replace('/^\/+|\/+$/s', '', $path);

        $segments = explode('/', $path);
        // TODO: добавить поддержку для путей из 4х сегментов
        $segment = end($segments);

        return explode(',', $segment);
    }
}