<?php

namespace EnterSite\Repository\Partial\ProductList;

use EnterAggregator\TranslateHelperTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterSite\Routing;
use EnterSite\Repository;
use EnterSite\Model;
use EnterSite\Model\Partial;

class MoreLink {
    use TemplateHelperTrait;

    /**
     * @param $pageNum
     * @param $limit
     * @param $count
     * @param \EnterModel\Product\Category|null $category
     * @return Partial\Link|null
     */
    public function getObject(
        $pageNum,
        $limit,
        $count,
        \EnterModel\Product\Category $category = null
    ) {
        $link = null;

        $rest = ($count - ($pageNum * $limit));
        if ($rest > 0) {
            $link = new Partial\Link();
            $link->widgetId = self::getWidgetId();

            //$link->name = sprintf('Показать еще %s', $rest < $limit ? $rest : $limit);
            $link->name = 'Показать еще';
            // FIXME
            if ($category) {
                $link->dataGa = $this->getTemplateHelper()->json([
                    'm_cat_show_more' => ['send', 'event', 'm_cat_show_more', $category->name],
                ]);
            }
        }

        return $link;
    }

    /**
     * @return string
     */
    public static function getId() {
        return 'id-productList-moreLink';
    }

    /**
     * @return string
     */
    public static function getWidgetId() {
        return self::getId() . '-widget';
    }
}