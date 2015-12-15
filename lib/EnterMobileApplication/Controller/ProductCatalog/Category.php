<?php

namespace EnterMobileApplication\Controller\ProductCatalog;

use Enter\Http;
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

        $userAuthToken = is_scalar($request->query['token']) ? (string)$request->query['token'] : null;

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

        if (strpos($categoryId, (new \EnterMobileApplication\Repository\MainMenu())->getSecretSaleElement()->id) === 0) {
            return $this->getResponseForSecretSale($request);
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

        // контроллер
        $controller = new \EnterAggregator\Controller\ProductList();
        // запрос для контроллера
        $controllerRequest = $controller->createRequest();
        $controllerRequest->config->mainMenu = false;
        $controllerRequest->config->parentCategory = false;
        $controllerRequest->config->branchCategory = false;
        // MAPI-43
        $controllerRequest->config->loadProductsForRootCategory = false;
        $controllerRequest->config->loadFiltersForRootCategory = false;
        $controllerRequest->config->loadSortingsForRootCategory = false;
        $controllerRequest->config->loadFiltersForMiddleCategory = false;
        $controllerRequest->config->loadSortingsForMiddleCategory = false;
        $controllerRequest->config->favourite = true;
        $controllerRequest->regionId = $regionId;
        $controllerRequest->categoryCriteria = ['id' => $categoryId]; // критерий получения категории товара
        $controllerRequest->pageNum = $pageNum;
        $controllerRequest->limit = $limit;
        $controllerRequest->sorting = $sorting;
        $controllerRequest->filterRepository = $filterRepository;
        $controllerRequest->baseRequestFilters = [];
        $controllerRequest->requestFilters = $requestFilters;
        $controllerRequest->userToken = $userAuthToken;
        // ответ от контроллера
        $controllerResponse = $controller->execute($controllerRequest);

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

        $response = [
            'category' => $this->getResponseForCategory($controllerResponse->category, $controllerResponse->catalogConfig),
            'productCount' => $controllerResponse->productUiPager ? $controllerResponse->productUiPager->count : null,
            'products' => $this->getProductList($controllerResponse->products),
            'filters' => $this->getFilterList($controllerResponse->filters),
            'sortings' => $this->getSortingList($controllerResponse->sortings),
        ];
        
        return new Http\JsonResponse($response);
    }

    /**
     * @param Model\Product\Category $category
     * @param Model\Product\Category\Config $categoryConfig
     * @return array
     */
    private function getResponseForCategory(Model\Product\Category $category, Model\Product\Category\Config $categoryConfig = null) {
        $maxLevel = $category->level + 1;
        $walkByCategory = function(\EnterModel\Product\Category $category) use (&$walkByCategory, &$maxLevel, &$categoryConfig) {
            $response = [
                'id'          => (string)$category->id,
                'name'        => $category->name,
                'media'       => $category->media,
                'hasChildren' => $category->hasChildren,
                'listingView' => ($categoryConfig && $categoryConfig->tchibo) ? '2' : '1', // MAPI-169
                'discount'    => null,
            ];

            if (($category->level < $maxLevel) && $category->children) {
                $response['children'] = [];
                foreach ($category->children as $child) {
                    $response['children'][] = $walkByCategory($child);
                }
            }

            return $response;
        };
        
        return $walkByCategory($category);
    }

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\Response
     */
    private function getResponseForSecretSale(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();
        $categoryRepository = new \EnterRepository\Product\Category();
        $productRepository = new \EnterRepository\Product();
        $filterRepository = new \EnterTerminal\Repository\Product\Filter(); // FIXME!!!

        $regionId = (new \EnterMobileApplication\Repository\Region())->getIdByHttpRequest($request);
        $userAuthToken = is_scalar($request->query['token']) ? (string)$request->query['token'] : null;
        $pageNum = (int)$request->query['page'] ?: 1;
        $limit = (int)$request->query['limit'];

        $promoUi = null;
        $categoryId = null;
        call_user_func(function() use(&$promoUi, &$categoryId, $request) {
            $ids = explode(':', trim((string)$request->query['categoryId']));
            if (isset($ids[1])) {
                $promoUi = $ids[1];
            }

            if (isset($ids[2])) {
                $categoryId = $ids[2];
            }
        });

        if (!$regionId) {
            throw new \Exception('Не указан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
        }

        if ($limit < 1) {
            throw new \Exception('limit не должен быть меньше 1', Http\Response::STATUS_BAD_REQUEST);
        }

        if ($limit > 40) {
            throw new \Exception('limit не должен быть больше 40', Http\Response::STATUS_BAD_REQUEST);
        }

        $sortings = (new \EnterRepository\Product\Sorting())->getObjectList();

        if (!empty($request->query['sort']['token']) && !empty($request->query['sort']['direction'])) {
            $sorting = new Model\Product\Sorting();
            $sorting->token = trim((string)$request->query['sort']['token']);
            $sorting->direction = trim((string)$request->query['sort']['direction']);
        } else {
            $sorting = reset($sortings);
        }

        $regionItemQuery = new Query\Region\GetItemById($regionId);
        $curl->prepare($regionItemQuery);

        /** @var \EnterQuery\User\GetItemByToken|null $userItemQuery */
        $userItemQuery = null;
        if (0 !== strpos($userAuthToken, 'anonymous-')) {
            $userItemQuery = new Query\User\GetItemByToken($userAuthToken);
            $curl->prepare($userItemQuery);
        }

        $secretSalePromoItemQuery = null;
        $secretSalePromoListQuery = null;
        if ($promoUi) {
            $secretSalePromoItemQuery = new \EnterQuery\Promo\SecretSale\GetItemByUi($promoUi);
            $curl->prepare($secretSalePromoItemQuery);
        } else {
            $secretSalePromoListQuery = new \EnterQuery\Promo\SecretSale\GetList();
            $curl->prepare($secretSalePromoListQuery);
        }

        $curl->execute();

        $region = (new \EnterRepository\Region())->getObjectByQuery($regionItemQuery);

        if ($userItemQuery) {
            $user = (new \EnterRepository\User())->getObjectByQuery($userItemQuery, false);
        } else {
            $user = null;
        }

        if (!$user) {
            throw new \Exception('Пользователь не авторизован', Http\Response::STATUS_UNAUTHORIZED);
        }

        /** @var Model\SecretSalePromo|null $secretSalePromo */
        $secretSalePromo = null;
        /** @var Model\SecretSalePromo[] $secretSalePromos */
        $secretSalePromos = [];

        if ($secretSalePromoItemQuery) {
            $secretSalePromo = $secretSalePromoItemQuery->getResult();
            if ($secretSalePromo) {
                $secretSalePromo = new Model\SecretSalePromo($secretSalePromo);
                $time = time();
                if ($secretSalePromo->startAt > $time || $secretSalePromo->endAt < $time) {
                    $secretSalePromo = null;
                }
            }
        } else if ($secretSalePromoListQuery) {
            $secretSalePromos = array_map(function($secretSalePromo) {
                return new Model\SecretSalePromo($secretSalePromo);
            }, $secretSalePromoListQuery->getResult());
        }

        $productCount = null;
        $productsOnPage = [];
        if ($secretSalePromo && $secretSalePromo->products) {
            $productListQueries = [];
            $productDescriptionListQueries = [];
            $productRatingListQueries = [];
            foreach (array_chunk(array_map(function(Model\Product $product) { return $product->ui; }, $secretSalePromo->products), $config->curl->queryChunkSize) as $uisInChunk) {
                $productListQuery = new Query\Product\GetListByUiList($uisInChunk, $region->id, ['related' => false]);
                $productDescriptionListQuery = new Query\Product\GetDescriptionListByUiList($uisInChunk, [
                    'media'       => true,
                    'media_types' => ['main'],
                    'category'    => true,
                    'label'       => true,
                    'brand'       => true,
                    'tag'         => true,
                    'model'       => true,
                ]);
                $productRatingListQuery = new Query\Product\Rating\GetListByProductUiList($uisInChunk);

                $curl->prepare($productListQuery);
                $curl->prepare($productDescriptionListQuery);
                $curl->prepare($productRatingListQuery);

                $productListQueries[] = $productListQuery;
                $productDescriptionListQueries[] = $productDescriptionListQuery;
                $productRatingListQueries[] = $productRatingListQuery;
            }

            $curl->execute();

            $secretSalePromo->products = $productRepository->getIndexedObjectListByQueryList($productListQueries, $productDescriptionListQueries);
            $productRepository->setRatingForObjectListByQueryList($secretSalePromo->products, $productRatingListQueries);

            $requestFilters = $filterRepository->getRequestObjectListByHttpRequest($request);

            (new \EnterRepository\Product\Category())->filterSecretSaleProducts($secretSalePromo->products, $categoryId, $requestFilters);

            // Сортировка товаров
            call_user_func(function() use(&$secretSalePromo, $sorting) {
                if (!$secretSalePromo->products) {
                    return;
                }

                switch ($sorting->token) {
                    case 'hits':
                        $compareFunction = function(Model\Product $product1, Model\Product $product2) {
                            $product1Rating = $product1->rating ? $product1->rating->score : 0;
                            $product2Rating = $product2->rating ? $product2->rating->score : 0;

                            if ($product1Rating == $product2Rating) {
                                if ($product1->isBuyable == $product2->isBuyable) {
                                    return 0;
                                } else if ($product1->isBuyable < $product2->isBuyable) {
                                    return -1;
                                } else {
                                    return 1;
                                }
                            } else if ($product1Rating < $product2Rating) {
                                return -1;
                            } else {
                                return 1;
                            }
                        };
                        break;
                    case 'price':
                        $compareFunction = function(Model\Product $product1, Model\Product $product2) {
                            if ($product1->price == $product2->price) {
                                return 0;
                            } else if ($product1->price < $product2->price) {
                                return -1;
                            } else {
                                return 1;
                            }
                        };
                        break;
                    default:
                        $compareFunction = function(Model\Product $product1, Model\Product $product2) {
                            if ($product1->isBuyable == $product2->isBuyable) {
                                $product1Rating = $product1->rating ? $product1->rating->score : 0;
                                $product2Rating = $product2->rating ? $product2->rating->score : 0;

                                if ($product1Rating == $product2Rating) {
                                    return 0;
                                } else if ($product1Rating < $product2Rating) {
                                    return -1;
                                } else {
                                    return 1;
                                }
                            } else if ($product1->isBuyable < $product2->isBuyable) {
                                return -1;
                            } else {
                                return 1;
                            }
                        };
                }

                usort($secretSalePromo->products, $compareFunction);

                if ('desc' === $sorting->direction) {
                    $secretSalePromo->products = array_reverse($secretSalePromo->products);
                }
            });

            $secretSalePromo->products = array_values($secretSalePromo->products);

            $productCount = count($secretSalePromo->products);
            $productsOnPage = array_slice($secretSalePromo->products, $limit * ($pageNum - 1), $limit);
        }

        return new Http\JsonResponse([
            'category' => call_user_func(function() use($secretSalePromo, $secretSalePromos, $categoryId, $categoryRepository, $region, $curl, $config) {
                $listingView = '3';
                $resultCategory = [];
                $secretSaleMenuElement = (new \EnterMobileApplication\Repository\MainMenu())->getSecretSaleElement();

                if ($secretSalePromo) {
                    if ($categoryId) {
                        $productCategoryRepository = new \EnterRepository\Product\Category();

                        $categoryItemQuery = new \EnterQuery\Product\Category\GetItemById($categoryId, $region->id);
                        $curl->prepare($categoryItemQuery);
                        $curl->execute();

                        $category = $productCategoryRepository->getObjectByQuery($categoryItemQuery);
                        if (!$category) {
                            $category = new \EnterModel\Product\Category();
                        }

                        $resultCategory = [
                            'id' => $secretSaleMenuElement->id . ':' . $secretSalePromo->ui . ':' . $category->id,
                            'name' => (string)$category->name,
                            'media' => $category->media,
                            'hasChildren' => false,
                            'listingView' => $listingView,
                            'discount' => [
                                'value' => (int)$secretSalePromo->discount,
                                'unit' => '%',
                                'endAt' => (int)$secretSalePromo->endAt,
                            ],
                            'children' => [],
                        ];
                    } else {
                        $resultCategory = [
                            'id' => $secretSaleMenuElement->id . ':' . $secretSalePromo->ui,
                            'name' => (string)$secretSalePromo->name,
                            'media' => $this->getResponseForSecretSaleMediaList($secretSalePromo->media),
                            'hasChildren' => false,
                            'listingView' => $listingView,
                            'discount' => [
                                'value' => (int)$secretSalePromo->discount,
                                'unit' => '%',
                                'endAt' => (int)$secretSalePromo->endAt,
                            ],
                            'children' => [],
                        ];

                        // Отключаем подкатегории промо до решения FCMS-998, т.к. моб. приложениям для корректной работы
                        // нужен флаг hasChildren для подкатегорий подкатегорий при запросе категории самого верхнего
                        // уровня (secretSale)
                        /*
                        $categoryUis = [];
                        foreach ($secretSalePromo->products as $product) {
                            if ($product->category) {
                                $rootCategoryUi = $categoryRepository->getRootObject($product->category)->ui;
                                if (!in_array($rootCategoryUi, $categoryUis, true)) {
                                    $categoryUis[] = $rootCategoryUi;
                                }
                            }
                        }

                        if ($categoryUis) {
                            $categoryListQuery = new \EnterQuery\Product\Category\GetListByUiList($categoryUis, $region->id);
                            $curl->prepare($categoryListQuery);
                            $curl->execute();

                            foreach ((new \EnterRepository\Product\Category())->getObjectListByQuery($categoryListQuery) as $category) {
                                $resultCategory['children'][] = [
                                    'id' => $secretSaleMenuElement->id . ':' . $secretSalePromo->ui . ':' . $category->id,
                                    'name' => (string)$category->name,
                                    'media' => $category->media,
                                    'hasChildren' => false,
                                    'listingView' => $listingView,
                                    'discount' => [
                                        'value' => (int)$secretSalePromo->discount,
                                        'unit' => '%',
                                        'endAt' => (int)$secretSalePromo->endAt,
                                    ],
                                    'children' => [],
                                ];
                            }
                        }
                        */
                    }
                } else if ($secretSalePromos) {
                    $media = new Model\MediaList();
                    $media->photos = $secretSaleMenuElement->media;

                    $resultCategory = [
                        'id' => (string)$secretSaleMenuElement->id,
                        'name' => (string)$secretSaleMenuElement->name,
                        'media' => $this->getResponseForSecretSaleMediaList($media, 'http://' . $config->hostname . ($config->version ? '/' . $config->version : '') . '/img/menu/250x250/secretSale.png'),
                        'hasChildren' => false,
                        'listingView' => $listingView,
                        'discount' => null,
                        'children' => [],
                    ];

                    foreach ($secretSalePromos as $secretSalePromo) {
                        $resultCategory['children'][] = [
                            'id' => $secretSaleMenuElement->id . ':' . $secretSalePromo->ui,
                            'name' => (string)$secretSalePromo->name,
                            'media' => $this->getResponseForSecretSaleMediaList($secretSalePromo->media),
                            'hasChildren' => false,
                            'listingView' => $listingView,
                            'discount' => [
                                'value' => (int)$secretSalePromo->discount,
                                'unit' => '%',
                                'endAt' => (int)$secretSalePromo->endAt,
                            ],
                            'children' => [],
                        ];
                    }
                }

                if (!empty($resultCategory['children'])) {
                    $resultCategory['hasChildren'] = true;
                }

                return $resultCategory;
            }),
            'productCount' => $productCount,
            'products' => $this->getProductList($productsOnPage),
            'filters' => call_user_func(function() use($secretSalePromo) {
                if (!$secretSalePromo || !$secretSalePromo->products) {
                    return [];
                }

                $prices = array_map(function(\EnterModel\Product $product) {
                    return $product->price;
                }, $secretSalePromo->products);

                $minPrice = min($prices);
                $maxPrice = max($prices);

                return [
                    [
                        'name' => 'Цена',
                        'token' => 'price',
                        'isSlider' => true,
                        'isMultiple' => false,
                        'min' => (float)$minPrice,
                        'max' => (float)$maxPrice,
                        'unit' => null,
                        'isSelected' => false,
                        'value' => null,
                        'option' => [
                            [
                                'id' => (string)$minPrice,
                                'token' => 'from',
                                'name' => 'от',
                                'quantity' => null
                            ],
                            [
                                'id' => (string)$maxPrice,
                                'token' => 'to',
                                'name' => 'до',
                                'quantity' => null
                            ],
                        ],
                    ],
                ];
            }),
            'sortings' => $productsOnPage ? $sortings : [],
        ]);
    }
    
    private function getResponseForSecretSaleMediaList(Model\MediaList $mediaList, $sourceUrl = null) {
        $mediaRepository = new \EnterRepository\Media();
        $media = reset($mediaList->photos);
        return [
            'photos' => [
                [
                    'uid' => null,
                    'contentType' => $media->contentType,
                    'type' => $media->type,
                    'tags' => ['main'],
                    'sources' => [
                        [
                            'width' => '96',
                            'height' => '96',
                            'type' => 'category_96x96',
                            'url' => $sourceUrl ?: $mediaRepository->getSourceObjectByItem($media, 'closed_sale_315x231')->url,
                        ],
                        [
                            'width' => '130',
                            'height' => '130',
                            'type' => 'category_130x130',
                            'url' => $sourceUrl ?: $mediaRepository->getSourceObjectByItem($media, 'closed_sale_315x231')->url,
                        ],
                        [
                            'width' => '163',
                            'height' => '163',
                            'type' => 'category_163x163',
                            'url' => $sourceUrl ?: $mediaRepository->getSourceObjectByItem($media, 'closed_sale_315x231')->url,
                        ],
                        [
                            'width' => '200',
                            'height' => '200',
                            'type' => 'category_200x200',
                            'url' => $sourceUrl ?: $mediaRepository->getSourceObjectByItem($media, 'closed_sale_315x231')->url,
                        ],
                        [
                            'width' => '350',
                            'height' => '350',
                            'type' => 'category_350x350',
                            'url' => $sourceUrl ?: $mediaRepository->getSourceObjectByItem($media, 'closed_sale_483x357')->url,
                        ],
                        [
                            'width' => '480',
                            'height' => '480',
                            'type' => 'category_480x480',
                            'url' => $sourceUrl ?: $mediaRepository->getSourceObjectByItem($media, 'closed_sale_651x483')->url,
                        ],
                        [
                            'width' => '1000',
                            'height' => '1000',
                            'type' => 'category_1000x1000',
                            'url' => $sourceUrl ?: $mediaRepository->getSourceObjectByItem($media, 'closed_sale_987x725')->url,
                        ],
                        [
                            'width' => '300',
                            'height' => '300',
                            'type' => 'original',
                            'url' => $sourceUrl ?: $mediaRepository->getSourceObjectByItem($media, 'closed_sale_483x357')->url,
                        ],
                    ],
                ],
            ],
        ];
    }
}
