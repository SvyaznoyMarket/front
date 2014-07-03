<?php

namespace EnterRepository\Product;

use Enter\Http;
use Enter\Curl\Query;
use EnterSite\ConfigTrait;
use EnterSite\LoggerTrait;
use EnterModel as Model;

class Filter {
    use ConfigTrait, LoggerTrait {
        ConfigTrait::getConfig insteadof LoggerTrait;
    }

    /**
     * @param Query $query
     * @return Model\Product\Filter[]
     */
    public function getObjectListByQuery(Query $query) {
        $filters = [];

        try {
            foreach ($query->getResult() as $item) {
                $filters[] = new Model\Product\Filter($item);
            }
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'action' => __METHOD__, 'tag' => ['repository']]);

            trigger_error($e, E_USER_ERROR);
        }

        return $filters;
    }

    /**
     * @param Model\Product\Category[] $categories
     * @return Model\Product\Filter[]
     */
    public function getObjectListByCategoryList(array $categories) {
        $filters = [];

        $categoryOptionData = [];
        foreach ($categories as $category) {
            $categoryOptionData[] = [
                'id'       => $category->id,
                'token'    => $category->id,
                'name'     => $category->name,
                'quantity' => $category->productCount,
                'image'    => $category->image,
            ];
        }
        $filters[] = new Model\Product\Filter([
            'filter_id' => 'category',
            'name'      => 'Категории',
            'type_id'   => Model\Product\Filter::TYPE_LIST,
            'options'   => $categoryOptionData,
        ]);

        return $filters;
    }
}