<?php

namespace EnterMobileApplication\Controller\ProductCatalog;

use Enter\Http;
use EnterAggregator\Model\Context;
use EnterMobileApplication\Controller;
use EnterQuery as Query;
use EnterModel as Model;

class Category {
    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        $filterRepository = new \EnterTerminal\Repository\Product\Filter(); // FIXME!!!

        // ид региона
        $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request); // FIXME
        if (!$regionId) {
            throw new \Exception('Не указан параметр regionId');
        }

        // ид категории
        $categoryId = trim((string)$request->query['categoryId']);
        if (!$categoryId) {
            throw new \Exception('Не указан параметр categoryId');
        }

        // номер страницы
        $pageNum = (int)$request->query['page'] ?: 1;

        // количество товаров на страницу
        $limit = (int)$request->query['limit'];
        if ($limit < 1) {
            throw new \Exception('limit не должен быть меньше 1');
        }
        if ($limit > 40) {
            throw new \Exception('limit не должен быть больше 40');
        }

        // сортировка
        $sorting = null;
        if (!empty($request->query['sort']['token']) && !empty($request->query['sort']['direction'])) {
            $sorting = new Model\Product\Sorting();
            $sorting->token = trim((string)$request->query['sort']['token']);
            $sorting->direction = trim((string)$request->query['sort']['direction']);
        }

        // фильтры в запросе
        $requestFilters = $filterRepository->getRequestObjectListByHttpRequest($request);
        // фильтр категории в http-запросе
        //$requestFilters[] = $filterRepository->getRequestObjectByCategory($category);

        $context = new Context\ProductCatalog();
        $context->mainMenu = false;
        $context->parentCategory = false;
        $context->branchCategory = false;
        $context->productOnlyForLeafCategory = true;
        $controllerResponse = (new \EnterAggregator\Controller\ProductList())->execute(
            $regionId,
            ['id' => $categoryId], // критерий получения категории товара
            $pageNum, // номер страницы
            $limit, // лимит
            $sorting, // сортировка
            $filterRepository, // репозиторий фильтров
            [],
            $requestFilters, // фильтры в http-запросе
            $context
        );

        // категория
        if (!$controllerResponse->category) {
            return (new Controller\Error\NotFound())->execute($request, sprintf('Категория товара #%s не найдена', $categoryId));
        }

        if ($controllerResponse->productIdPager) {
            $response = $this->getResponseForLeafCategory(
                $controllerResponse->category,
                $controllerResponse->productIdPager,
                $controllerResponse->products,
                $controllerResponse->filters,
                $controllerResponse->sortings
            );
        } else {
            $response = $this->getResponseForBranchCategory(
                $controllerResponse->category
            );
        }

        return new Http\JsonResponse($response);
    }

    /**
     * @param Model\Product\Category $category
     * @param Model\Product\IdPager $idPager
     * @param Model\Product[] $products
     * @param Model\Product\Filter[] $filters
     * @param Model\Product\Sorting[] $sortings
     * @return array
     */
    private function getResponseForLeafCategory(
        Model\Product\Category $category,
        Model\Product\IdPager $idPager,
        array $products,
        array $filters,
        array $sortings
    ) {
        $response = [
            'category'     => [
                'id'           => $category->id,
                'name'         => $category->name,
                'image'        => $category->image,
                'productCount' => $category->productCount,
            ],
            'products'     => [],
            'productCount' => $idPager->count,
            'filters'      => [],
            'sortings'     => [],
        ];

        // товары
        foreach ($products as $product) {
            $response['products'][] = [
                'id'                   => $product->id,
                'article'              => $product->article,
                'webName'              => $product->webName,
                'namePrefix'           => $product->namePrefix,
                'name'                 => $product->name,
                'isBuyable'            => $product->isBuyable,
                'isInShopOnly'         => $product->isInShopOnly,
                'isInShopStockOnly'    => $product->isInShopStockOnly,
                'isInShopShowroomOnly' => $product->isInShopShowroomOnly,
                'brand'                => $product->brand ? [
                    'id'   => $product->brand->id,
                    'name' => $product->brand->name,
                ] : null,
                'price'                => $product->price,
                'oldPrice'             => $product->oldPrice,
                'labels'               => array_map(function(Model\Product\Label $label) {
                    return [
                        'id'    => $label->id,
                        'name'  => $label->name,
                        'image' => $label->image,
                    ];
                }, $product->labels),
                'media'                => $product->media,
                'rating'               => $product->rating ? [
                    'score'       => $product->rating->score,
                    'starScore'   => $product->rating->starScore,
                    'reviewCount' => $product->rating->reviewCount,
                ] : null,
            ];
        }

        // фильтры
        foreach ($filters as $filter) {
            $response['filters'][] = [
                'token'      => $filter->token,
                'isSlider'   => in_array($filter->typeId, [3, 6]),
                'isMultiple' => $filter->isMultiple,
                'min'        => $filter->min,
                'max'        => $filter->max,
                'unit'       => $filter->unit,
                'option'     => array_map(function(Model\Product\Filter\Option $option) {
                    return [
                        'id'       => $option->id,
                        'token'    => $option->token,
                        'name'     => $option->name,
                        'quantity' => $option->quantity,
                        'image'    => $option->image,
                    ];
                }, $filter->option),
            ];
        }

        // сортировка
        foreach ($sortings as $sorting) {
            $response['sortings'][] = [
                'token'     => $sorting->token,
                'name'      => $sorting->name,
                'direction' => $sorting->direction,
            ];
        }

        return $response;
    }

    /**
     * @param Model\Product\Category $category
     * @return array
     */
    private function getResponseForBranchCategory(
        Model\Product\Category $category
    ) {
        $response = [
            'category' => null,
        ];

        $maxLevel = $category->level + 1;

        $walkByCategory = function(\EnterModel\Product\Category $category) use (&$walkByCategory, &$maxLevel) {
            $response = [
                'id'       => $category->id,
                'name'     => $category->name,
                'image'    => $category->image,
            ];

            if (($category->level < $maxLevel) && $category->children) {
                $response['children'] = [];
                foreach ($category->children as $child) {
                    $response['children'][] = $walkByCategory($child);
                }
            }

            return $response;
        };

        // категория
        $response['category'] = $walkByCategory($category);

        return $response;
    }
}
