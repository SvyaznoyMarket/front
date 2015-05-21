<?php

namespace Enter\Helper;

class Date {
    /**
     * @param \DateTime $date
     * @return string
     */
    public function dateToRu(\DateTime $date) {
        $monthsEnRu = [
            'January'   => 'января',
            'February'  => 'февраля',
            'March'     => 'марта',
            'April'     => 'апреля',
            'May'       => 'мая',
            'June'      => 'июня',
            'July'      => 'июля',
            'August'    => 'августа',
            'September' => 'сентября',
            'October'   => 'октября',
            'November'  => 'ноября',
            'December'  => 'декабря',
        ];
        $dateEn = $date->format('j F Y');
        $dateRu = $dateEn;
        foreach ($monthsEnRu as $monthsEn => $monthsRu) {
            if (preg_match("/$monthsEn/", $dateEn)) {
                $dateRu = preg_replace("/$monthsEn/", $monthsRu, $dateEn);
            }
        }

        return $dateRu;
    }

    /**
     * @param \DateTime $date
     * @param string $format Формат для возврата
     * @return string
     */
    public function humanizeDate(\DateTime $date, $format = 'd.m.Y') {
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