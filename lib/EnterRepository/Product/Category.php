<?php

namespace EnterRepository\Product;

use Enter\Http;
use Enter\Curl\Query;
use EnterAggregator\ConfigTrait;
use EnterModel as Model;

class Category {
    use ConfigTrait;

    /**
     * @param Http\Request $request
     * @return string
     */
    public function getTokenByHttpRequest(Http\Request $request) {
        $token = null;

        if ($request->query['categoryPath']) {
            $token = explode('/', $request->query['categoryPath']);
            $token = end($token);
        } else if ($request->query['categoryToken']) {
            $token = $request->query['categoryToken'];
        }

        return $token;
    }

    /**
     * @param Http\Request $request
     * @return string
     */
    public function getLinkByHttpRequest(Http\Request $request) {
        return '/catalog/' . $request->query['categoryPath'];
    }

    /**
     * Возвращает список категорий без дочерних узлов
     *
     * @param Query $query
     * @return Model\Product\Category[]
     */
    public function getObjectListByQuery(Query $query) {
        $categories = [];

        foreach ($query->getResult() as $item) {
            if (isset($item['children'])) unset($item['children']);

            $categories[] = new Model\Product\Category($item);
        }

        return $categories;
    }

    /**
     * Преобразовывает древовидную структуру данных в линейную
     * и возвращает список категорий от верхнего уровня до нижнего (branch)
     *
     * @param \Enter\Curl\Query $query
     * @return Model\Product\Category[]
     */
    public function getAscendantListByQuery(Query $query) {
        $categories = [];

        $walk = function(array $item) use (&$walk, &$categories) {
            $childItem = isset($item['children'][0]['id']) ? $item['children'][0] : null;
            // удаляем children, т.к. он не загружен полностью - в нем только один элемент
            if (isset($item['children'])) unset($item['children']);
            $categories[] = new Model\Product\Category($item);

            if ($childItem) {
                $walk($childItem);
            }
        };

        if ($item = $query->getResult()) {
            $walk($item);
        }

        return $categories;
    }

    /**
     * @param Query $query
     * @return Model\Product\Category
     */
    public function getObjectByQuery(Query $query) {
        $category = null;

        if ($item = $query->getResult()) {
            $category = new Model\Product\Category($item);
        }

        return $category;
    }

    /**
     * К переданной категории добавляет предков и детей
     *
     * @param Model\Product\Category $category
     * @param Query $query
     */
    public function setBranchForObjectByQuery(Model\Product\Category $category, Query $query) {
        $walk = function($data, Model\Product\Category $parent = null) use (&$walk, &$category) {
            foreach ($data as $item) {
                $item += ['uid' => null, 'level' => null, 'children' => null];

                if (!$item['uid']) continue;
                $iCategory = new Model\Product\Category($item);
                if ($parent) {
                    $parent->children[] = $iCategory;
                }

                if ($iCategory->level < $category->level) { // предки
                    $category->ascendants[] = $iCategory;
                } else if ($iCategory->level == ($category->level + 1)) { // прямые потомки (дети) категории
                    $category->children[] = $iCategory;
                }

                $walk($item['children'], $iCategory);
            }
        };

        $walk($query->getResult());

        $category->parent = reset($category->ascendants);
        $category->ascendants = array_reverse($category->ascendants, true);
    }

    /**
     * @param Model\Product[] $products
     * @param string[] $categoryTokens
     * @return Model\Product\Category[]
     */
    public function getIndexedObjectListByProductListAndTokenList(array $products, array $categoryTokens) {
        $categoriesById = [];

        foreach ($products as $product) {
            if (!$product->category) continue;

            $isValid = false;
            foreach (array_merge([$product->category], $product->category->ascendants) as $category) {
                /** @var Model\Product\Category $category */
                if (in_array($category->token, $categoryTokens)) {
                    $isValid = true;
                    break;
                }
            }

            if ($isValid && !isset($categoriesById[$product->category->id])) {
                $categoriesById[$product->category->id] = $product->category;
            }
        }

        return $categoriesById;
    }

    /**
     * @param Model\SearchResult $searchResult
     * @return Model\Product\Category[]
     */
    public function getObjectListBySearchResult(Model\SearchResult $searchResult) {
        $categories = [];
        foreach ($searchResult->categories as $searchCategory) {
            $category = new Model\Product\Category();
            $category->id = $searchCategory->id;
            $category->name = $searchCategory->name;
            $category->productCount = $searchCategory->productCount;
            $category->image = $searchCategory->image;

            $categories[] = $category;
        }

        return $categories;
    }
}