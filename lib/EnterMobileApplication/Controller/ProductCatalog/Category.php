<?php

namespace EnterMobileApplication\Controller\ProductCatalog;

use Enter\Http;
use EnterAggregator\Model\Context;
use EnterMobileApplication\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterMobileApplication\Controller;
use EnterQuery as Query;
use EnterModel as Model;

class Category {
    use ConfigTrait, CurlTrait;
    use Controller\ProductListingTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();
        $filterRepository = new \EnterTerminal\Repository\Product\Filter(); // FIXME!!!

        // ид региона
        $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request); // FIXME
        if (!$regionId) {
            throw new \Exception('Не указан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
        }

        // ид категории
        $categoryId = trim((string)$request->query['categoryId']);
        if (!$categoryId) {
            throw new \Exception('Не указан параметр categoryId', Http\Response::STATUS_BAD_REQUEST);
        }

        // номер страницы
        $pageNum = (int)$request->query['page'] ?: 1;

        // количество товаров на страницу
        $limit = (int)$request->query['limit'];
        if ($limit < 1) {
            throw new \Exception('limit не должен быть меньше 1', Http\Response::STATUS_BAD_REQUEST);
        }
        if ($limit > 40) {
            throw new \Exception('limit не должен быть больше 40', Http\Response::STATUS_BAD_REQUEST);
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
        $context->productOnlyForLeafCategory = false;
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
            if ($config->region->defaultId != $regionId) {
                $categoryItemQuery = new Query\Product\Category\GetItemById($categoryId, $config->region->defaultId);
                $curl->prepare($categoryItemQuery)->execute();

                if ($categoryItemQuery->getResult()) {
                    return (new Controller\Error\NotFoundInRegion())->execute($request, 'Нет товаров в вашем регионе');
                }
            }

            return (new Controller\Error\NotFound())->execute($request, sprintf('Категория товара #%s не найдена', $categoryId));
        }

        // ответ
        if ($controllerResponse->productUiPager) {
            $response = $this->getResponseForLeafCategory(
                $controllerResponse->category,
                $controllerResponse->productUiPager,
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
     * @param Model\Product\UiPager $uiPager
     * @param Model\Product[] $products
     * @param Model\Product\Filter[] $filters
     * @param Model\Product\Sorting[] $sortings
     * @return array
     */
    private function getResponseForLeafCategory(
        Model\Product\Category $category,
        Model\Product\UiPager $uiPager,
        array $products,
        array $filters,
        array $sortings
    ) {
        $response = [
            'category'     => null,
            'productCount' => $uiPager->count,
            'products'     => [],
            'filters'      => [],
            'sortings'     => [],
        ];

        $maxLevel = $category->level + 1;
        $walkByCategory = function(\EnterModel\Product\Category $category) use (&$walkByCategory, &$maxLevel) {
            $response = [
                'id'          => $category->id,
                'name'        => $category->name,
                'media'       => $category->media,
                'hasChildren' => $category->hasChildren,
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

        // товары
        $response['products'] = $this->getProductList($products);

        // фильтры
        $response['filters'] = $this->getFilterList($filters);

        // сортировка
        $response['sortings'] = $this->getSortingList($sortings);

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
                'id'          => $category->id,
                'name'        => $category->name,
                'media'       => $category->media,
                'hasChildren' => $category->hasChildren,
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
