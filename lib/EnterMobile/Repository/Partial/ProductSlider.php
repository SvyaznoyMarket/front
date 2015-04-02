<?php

namespace EnterMobile\Repository\Partial;

use EnterMobile\Model;
use EnterMobile\Model\Partial;

class ProductSlider {
    /**
     * @param string $name
     * @param string|null $url
     * @param string|null $ga
     * @return Model\Partial\ProductSlider
     */
    public function getObject(
        $name,
        $url = null,
        $ga = null
    ) {
        $slider = new Partial\ProductSlider();

        $slider->widgetId = self::getWidgetId($name);
        $slider->dataUrl = $url;
        $slider->dataName = $name;
        $slider->dataGa = $ga;

        return $slider;
    }

    /**
     * @param $name
     * @return string
     */
    public static function getWidgetId($name) {
        return 'id-productSlider-' . $name;
    }
}