<?php

namespace EnterRepository\Product;

use Enter\Http;
use Enter\Curl\Query;
use EnterAggregator\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterModel as Model;

class Category {
    use ConfigTrait, LoggerTrait;

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
     * @param Query|null $availableQuery
     * @throws \Exception
     */
    public function setBranchForObjectByQuery(Model\Product\Category $category, Query $query, Query $availableQuery = null) {
        $availableDataByUi = null;
        try {
            $availableQueryResult = $availableQuery ? $availableQuery->getResult() : [];
            if ($availableQueryResult) {
                foreach ($availableQueryResult as $item) {
                    $item += ['id' => null, 'uid' => null, 'product_count' => null];

                    if (!$item['uid'] || !$item['product_count']) continue;

                    $availableDataByUi[$item['uid']] = $item;
                }
            }
        } catch (\Exception $e) {
            trigger_error($e, E_USER_ERROR);
        }

        $walk = function($data, Model\Product\Category $parent = null) use (&$walk, &$category, &$availableDataByUi) {
            foreach ($data as $item) {
                $item += ['uid' => null, 'level' => null, 'children' => null, 'has_children' => null];
                if (!$item['uid']) continue;

                $iCategory = new Model\Product\Category($item);
                $iCategory->hasChildren = (bool)$item['has_children'];
                $iCategory->parent = $parent;

                // фильтрация
                if ((null !== $availableDataByUi)) {
                    if (!array_key_exists($iCategory->ui, $availableDataByUi)) {
                        continue;
                    }
                }

                if ($iCategory->level < $category->level) { // предки
                    $category->parent = $iCategory;
                } else if ($iCategory->ui == $category->ui) { // категория
                    $category->hasChildren = $iCategory->hasChildren;
                } else if ($parent && ($parent->ui == $category->ui)) { // дети
                    $category->children[] = $iCategory;
                }

                $walk($item['children'], $iCategory);
            }
        };

        $walk($query->getResult());

        $category->children = array_values($category->children);
    }

    /**
     * @return Model\Product\Category
     */
    public function getRootObject(Model\Product\Category $category) {
        $root = $category;
        while ($root->parent) {
            $root = $root->parent;
        }
        
        return $root;
    }
    
    /**
     * @return Model\Product\Category
     */
    public function getAscendantList(Model\Product\Category $category) {
        $ascendants = [];
        while ($category = $category->parent) {
            $ascendants[] = $category;
        }
        
        return array_reverse($ascendants);
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
            
            $category = $product->category;
            do {
                if (in_array($category->token, $categoryTokens)) {
                    $isValid = true;
                    break;
                }
            } while ($category = $category->parent);

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

            $categories[] = $category;
        }

        return $categories;
    }

    /**
     * @param Query $query
     * @return \EnterModel\Product\Category\Config|null
     */
    public function getConfigObjectByQuery(Query $query) {
        $object = null;

        try {
            $item = $query->getResult();
            if ($item) {
                $object = new Model\Product\Category\Config($item);
            }
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['repository']]);
            //trigger_error($e, E_USER_ERROR);
        }

        return $object;
    }
}