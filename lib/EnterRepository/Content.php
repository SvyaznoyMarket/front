<?php

namespace EnterRepository;

class Content {
    public function getTokenByPath($path) {
        $path = preg_replace('/^\/+|\/+$/s', '', $path);

        $segments = explode('/', $path);
        // TODO: добавить поддержку для путей из 4х сегментов
        if (count($segments) <= 3)
            return end($segments);

        return null;
    }
}