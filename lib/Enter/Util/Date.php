<?php

namespace Enter\Util;

class Date {
    /**
     * @param \DateTime $date
     * @param string $format Формат для возврата
     * @return string
     */
    public static function humanizeDate(\DateTime $date, $format = 'd.m.Y') {
        $formatted = $date->format($format);

        $namesByDay = [
            0 => 'Сегодня',
            1 => 'Завтра',
            2 => 'Послезавтра',
        ];

        $now = new \DateTime('now');

        foreach ($namesByDay as $day => $name) {
            if ($day > 0) {
                $now->modify('+1 day');
            }

            if ($formatted == $now->format($format)) {
                return $name;
            }
        }

        return $formatted;
    }
}