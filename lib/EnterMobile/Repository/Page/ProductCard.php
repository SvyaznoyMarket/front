<?php

namespace EnterMobile\Repository\Page;

use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterAggregator\DateHelperTrait;
use EnterAggregator\TranslateHelperTrait;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Partial;
use EnterMobile\Model\Page\ProductCard as Page;

class ProductCard {
    use ConfigTrait, LoggerTrait, RouterTrait, DateHelperTrait, TranslateHelperTrait;

    /**
     * @param Page $page
     * @param ProductCard\Request $request
     */
    public function buildObjectByRequest(Page $page, ProductCard\Request $request) {
        (new Repository\Page\DefaultPage)->buildObjectByRequest($page, $request);

        $config = $this->getConfig();
        $router = $this->getRouter();
        $dateHelper = $this->getDateHelper();
        $translateHelper = $this->getTranslateHelper();

        $cartProductButtonRepository = new Repository\Partial\Cart\ProductButton();
        $cartProductReserveButtonRepository = new Repository\Partial\Cart\ProductReserveButton();
        $cartSpinnerRepository = new Repository\Partial\Cart\ProductSpinner();
        $productCardRepository = new Repository\Partial\ProductCard();
        $ratingRepository = new Repository\Partial\Rating();
        $productSliderRepository = new Repository\Partial\ProductSlider();

        $productModel = $request->product;

        // заголовок
        $page->title = $productModel->name . ' - Enter';

        $page->dataModule = 'product.card';

        // хлебные крошки
        $page->breadcrumbBlock = new Model\Page\DefaultPage\BreadcrumbBlock();
        $breadcrumb = new Model\Page\DefaultPage\BreadcrumbBlock\Breadcrumb();
        $breadcrumb->name = $productModel->name;
        $breadcrumb->url = $productModel->link;

        $page->breadcrumbBlock->breadcrumbs[] = $breadcrumb;

        // содержание
        $page->content->product->name = $productModel->webName;
        $page->content->product->namePrefix = $productModel->namePrefix;
        $page->content->product->article = $productModel->article;
        $page->content->product->description = $productModel->description;
        $page->content->product->price = $productModel->price;
        $page->content->product->shownPrice = $productModel->price ? number_format((float)$productModel->price, 0, ',', ' ') : null;
        $page->content->product->oldPrice = $productModel->oldPrice;
        $page->content->product->shownOldPrice = $productModel->oldPrice ? number_format((float)$productModel->oldPrice, 0, ',', ' ') : null;
        $page->content->product->cartButtonBlock = (new Repository\Partial\ProductCard\CartButtonBlock())->getObject($productModel);
        $page->content->product->brand = $productModel->brand;
        $page->content->product->labels = $productModel->labels;

        // доставка товара
        if ((bool)$productModel->nearestDeliveries) {
            $page->content->product->deliveryBlock = new Page\Content\Product\DeliveryBlock();
            foreach ($productModel->nearestDeliveries as $deliveryModel) {
                if (\EnterModel\Product\NearestDelivery::TOKEN_NOW == $deliveryModel->token) continue;

                $delivery = new Page\Content\Product\DeliveryBlock\Delivery();

                if (\EnterModel\Product\NearestDelivery::TOKEN_STANDARD == $deliveryModel->token) {
                    $delivery->name = 'Доставка';
                } else if (\EnterModel\Product\NearestDelivery::TOKEN_SELF == $deliveryModel->token) {
                    $delivery->name = 'Самовывоз';
                } else if (\EnterModel\Product\NearestDelivery::TOKEN_NOW == $deliveryModel->token) {
                    $delivery->deliveredAtText = 'Сегодня есть в магазинах';
                } else {
                    continue;
                }

                if (in_array($deliveryModel->token, [\EnterModel\Product\NearestDelivery::TOKEN_STANDARD, \EnterModel\Product\NearestDelivery::TOKEN_SELF])) {
                    $delivery->priceText = !$deliveryModel->price
                        ? 'бесплатно'
                        : (number_format((float)$deliveryModel->price, 0, ',', ' ') . ' p')
                    ;
                    if ($deliveryModel->deliveredAt) {
                        $delivery->deliveredAtText = $translateHelper->humanizeDate($deliveryModel->deliveredAt);
                    }
                }

                $delivery->token = $deliveryModel->token;

                $page->content->product->deliveryBlock->deliveries[] = $delivery;
            }
        }

        // состояние магазинов
        if ((bool)$productModel->shopStates) {
            $page->content->product->shopStateBlock = new Page\Content\Product\ShopStateBlock();
            foreach ($productModel->shopStates as $shopStateModel) {
                if (!$shopStateModel->shop) continue;

                $shopState = new Page\Content\Product\ShopStateBlock\State();

                $shopState->name = $shopStateModel->shop->name;
                $shopState->address = $shopStateModel->shop->address;
                $shopState->url = $shopStateModel->shop->region
                    ? $router->getUrlByRoute(new Routing\ShopCard\Get($shopStateModel->shop->token, $shopStateModel->shop->region->token))
                    : $router->getUrlByRoute(new Routing\Shop\Index());
                $shopState->regime = $shopStateModel->shop->regime;
                $shopState->isInShowroomOnly = !$shopStateModel->quantity && ($shopStateModel->showroomQuantity > 0);
                $shopState->cartButton = $cartProductReserveButtonRepository->getObject($productModel, $shopStateModel);
                $shopState->subway = isset($shopStateModel->shop->subway[0]) ? [
                    'name'  => $shopStateModel->shop->subway[0]->name,
                    'color' => isset($shopStateModel->shop->subway[0]->line)
                        ? $shopStateModel->shop->subway[0]->line->color
                        : null
                    ,
                ] : false;

                $page->content->product->shopStateBlock->states[] = $shopState;
            }

            $stateCount = count($page->content->product->shopStateBlock->states);
            if (!$stateCount) {
                $page->content->product->shopStateBlock = false;
            } else {
                $page->content->product->shopStateBlock->shownCount = 'Есть в ' . $stateCount . ' ' . $translateHelper->numberChoice($stateCount, ['магазине', 'магазинах', 'магазинах']);
                $page->content->product->shopStateBlock->hasOnlyOne = 1 === $stateCount;
            }
        }

        // фотографии товара
        foreach ($productModel->media->photos as $i => $photoModel) {
            $photo = new Page\Content\Product\Photo();
            $photo->name = $productModel->name;
            $photo->url = (string)(new Routing\Product\Media\GetPhoto($photoModel->source, $photoModel->id, 3));
            $photo->previewUrl = (string)(new Routing\Product\Media\GetPhoto($photoModel->source, $photoModel->id, 0));
            $photo->originalUrl = (string)(new Routing\Product\Media\GetPhoto($photoModel->source, $photoModel->id, 5));

            $page->content->product->photos[] = $photo;

            if (0 == $i) {
                $page->content->product->mainPhoto = $photo;
            }
        }

        // видео товара
        if ((bool)$productModel->media->videos) {
            $page->content->product->hasVideo = true;
            foreach ($productModel->media->videos as $videoModel) {
                $video = new Page\Content\Product\Video();
                $video->content = $videoModel->content;

                $page->content->product->videos[] = $video;
            }
        }

        // 3d фото товара (maybe3d)
        if ((bool)$productModel->media->photo3ds) {
            $page->content->product->hasPhoto3d = true;
            foreach ($productModel->media->photo3ds as $photo3dModel) {
                $photo3d = new Page\Content\Product\Photo3d();
                $photo3d->source = $photo3dModel->source;

                $page->content->product->photo3ds[] = $photo3d;
            }
        }

        // характеристики товара
        $groupedPropertyModels = [];
        foreach ($productModel->properties as $propertyModel) {
            if (!isset($groupedPropertyModels[$propertyModel->groupId])) {
                $groupedPropertyModels[$propertyModel->groupId] = [];
            }

            $groupedPropertyModels[$propertyModel->groupId][] = $propertyModel;
        }

        foreach ($productModel->propertyGroups as $propertyGroupModel) {
            if (!isset($groupedPropertyModels[$propertyGroupModel->id][0])) continue;

            $propertyChunk = new Page\Content\Product\PropertyChunk();

            $property = new Page\Content\Product\PropertyChunk\Property();
            $property->isTitle = true;
            $property->name = $propertyGroupModel->name;
            $propertyChunk->properties[] = $property;

            foreach ($groupedPropertyModels[$propertyGroupModel->id] as $propertyModel) {
                /** @var \EnterModel\Product\Property $propertyModel */
                $property = new Page\Content\Product\PropertyChunk\Property();
                $property->isTitle = false;
                $property->name = $propertyModel->name;
                $property->value = $propertyModel->shownValue . ($propertyModel->unit ? (' ' . $propertyModel->unit) : '');
                $propertyChunk->properties[] = $property;
            }

            $page->content->product->propertyChunks[] = $propertyChunk;
        }

        // рейтинг товара
        if ($productModel->rating) {
            $rating = new Partial\Rating();
            $rating->reviewCount = $productModel->rating->reviewCount;
            $rating->stars = $ratingRepository->getStarList($productModel->rating->starScore);

            $page->content->product->rating = $rating;
        }

        // состав набора
        $page->content->product->kitBlock = false;
        if ($productModel->relation && (bool)$productModel->relation->kits) {
            $page->content->product->kitBlock = new Page\Content\Product\KitBlock();
            $page->content->product->kitBlock->isLocked = $productModel->isKitLocked;

            $cartProductsById = [];
            $count = 0;
            $sum = 0;
            foreach ($productModel->relation->kits as $kitProductModel) {
                $cartProductsById[$kitProductModel->id] = new \EnterModel\Cart\Product([
                    'id'       => $kitProductModel->id,
                    'quantity' => $kitProductModel->kitCount,
                ]);

                $sum += $kitProductModel->kitCount * $kitProductModel->price;
                $count += $kitProductModel->kitCount;

                $kit = new Page\Content\Product\KitBlock\Product();
                $kit->name = $kitProductModel->name;
                $kit->url = $kitProductModel->link;
                $kit->quantity = $kitProductModel->kitCount;
                $kit->shownPrice = $kitProductModel->price ? number_format((float)$kitProductModel->price, 0, ',', ' ') : null;
                $kit->shownSum = $kitProductModel->price ? number_format((float)$kitProductModel->price * $kitProductModel->kitCount, 0, ',', ' ') : null;
                if (isset($kitProductModel->media->photos[0])) {
                    $photoModel = $kitProductModel->media->photos[0];
                    $kit->photoUrl = (string)(new Routing\Product\Media\GetPhoto($photoModel->source, $photoModel->id, 3));
                }

                if (isset($kitProductModel->nearestDeliveries[0])) {
                    /** @var \DateTime|null $deliveredDate */
                    $deliveredDate = $kitProductModel->nearestDeliveries[0]->deliveredAt ?: null;
                    if ($deliveredDate) {
                        $kit->deliveryDate = $deliveredDate->format('d.m.Y');
                    }
                }

                foreach ($kitProductModel->properties as $propertyModel) {
                    if ('Ширина' == $propertyModel->name) {
                        $kit->width = $propertyModel->value;
                        $kit->unit = $propertyModel->unit;
                    } else if ('Высота' == $propertyModel->name) {
                        $kit->height = $propertyModel->value;
                        $kit->unit = $propertyModel->unit;
                    } else if ('Глубина' == $propertyModel->name) {
                        $kit->depth = $propertyModel->value;
                        $kit->unit = $propertyModel->unit;
                    }

                }

                $kit->cartSpinner = $cartSpinnerRepository->getObject(
                    $kitProductModel,
                    new \EnterModel\Cart\Product(['quantity' => $kitProductModel->kitCount]),
                    true,
                    Repository\Partial\Cart\ProductButton::getId($productModel->id, false),
                    false,
                    $router->getUrlByRoute(new Routing\Product\QuantityAvailabilityList())
                );

                $kit->isHidden = !$kitProductModel->kitCount;

                $page->content->product->kitBlock->products[] = $kit;
            }

            $page->content->product->kitBlock->shownSum = number_format((float)$sum, 0, ',', ' ');
            $page->content->product->kitBlock->shownQuantity = 'Итого за ' . $count . ' ' . $translateHelper->numberChoice($count, ['предмет', 'предмета', 'предметов']);
            $page->content->product->kitBlock->cartButton = $cartProductButtonRepository->getListObject(
                array_reverse($productModel->relation->kits),
                $cartProductsById,
                $productModel->id,
                false,
                '+' // quantitySign
            );
            $page->content->product->kitBlock->resetDataValue = $page->content->product->kitBlock->cartButton->dataValue;
        }

        // аксессуары товара
        if ((bool)$productModel->relation->accessories) {
            $page->content->product->accessorySlider = $productSliderRepository->getObject('accessorySlider');
            $page->content->product->accessorySlider->count = count($productModel->relation->accessories);
            foreach ($productModel->relation->accessories as $accessoryModel) {
                $page->content->product->accessorySlider->productCards[] = $productCardRepository->getObject($accessoryModel, $cartProductButtonRepository->getObject($accessoryModel));
            }

            foreach ($request->accessoryCategories as $categoryModel) {
                $category = new Partial\ProductSlider\Category();
                $category->id = $categoryModel->id;
                $category->name = $categoryModel->name;

                $page->content->product->accessorySlider->categories[] = $category;
            }
            if ((bool)$page->content->product->accessorySlider->categories) {
                $page->content->product->accessorySlider->hasCategories = true;

                $category = new Partial\ProductSlider\Category();
                $category->id = '0';
                $category->name = 'Популярные аксессуары';

                array_unshift($page->content->product->accessorySlider->categories, $category);
            }
        }

        // рекомендации товара
        $recommendListUrl = $router->getUrlByRoute(new Routing\Product\GetRecommendedList($productModel->id));
        // alsoBought slider
        $page->content->product->alsoBoughtSlider = $productSliderRepository->getObject('alsoBoughtSlider', $recommendListUrl);
        $page->content->product->alsoBoughtSlider->count = 0;
        $page->content->product->alsoBoughtSlider->hasCategories = false;
        // alsoViewed slider
        $page->content->product->alsoViewedSlider = $productSliderRepository->getObject('alsoViewedSlider', $recommendListUrl);
        $page->content->product->alsoViewedSlider->count = 0;
        $page->content->product->alsoViewedSlider->hasCategories = false;
        // similar slider
        $page->content->product->similarSlider = $productSliderRepository->getObject('similarSlider', $recommendListUrl);
        $page->content->product->similarSlider->count = 0;
        $page->content->product->similarSlider->hasCategories = false;

        // отзывы товара
        if ((bool)$productModel->reviews) {
            $page->content->product->reviewBlock = new Page\Content\Product\ReviewBlock();
            foreach ($productModel->reviews as $reviewModel) {
                $review = new Page\Content\Product\ReviewBlock\Review();
                $review->author = $reviewModel->author;
                $review->createdAt = $reviewModel->createdAt ? $dateHelper->dateToRu($reviewModel->createdAt): null;
                $review->extract = $reviewModel->extract;
                $review->cons = $reviewModel->cons;
                $review->pros = $reviewModel->pros;
                $review->stars = $ratingRepository->getStarList($reviewModel->starScore);

                $page->content->product->reviewBlock->reviews[] = $review;
            }
        }

        // модели товара
        if ((bool)$productModel->model && (bool)$productModel->model->properties) {
            $page->content->product->hasModel = true;

            // значения свойств, индексированные по ид
            $propertyValuesById = [];
            foreach ($productModel->properties as $propertyModel) {
                $propertyValuesById[$propertyModel->id] = $propertyModel->value;
            }

            foreach ([
                 0 => [0, 1], // первое свойство модели
                 1 => [1, count($productModel->properties) - 1] // остальные свойства модели (будут скрыты по умолчанию)
            ] as $i => $range) {
                $modelBlock = new Page\Content\Product\ModelBlock();
                foreach (array_slice($productModel->model->properties, $range[0], $range[1]) as $propertyModel) {
                    /** @var \EnterModel\Product\ProductModel\Property $propertyModel */
                    $property = new Page\Content\Product\ModelBlock\Property();
                    //$property->name = !$propertyModel->isImage ? $propertyModel->name : null;
                    $property->name = $propertyModel->name;
                    $property->isImage = $propertyModel->isImage;
                    foreach ($propertyModel->options as $optionModel) {
                        $option = new Page\Content\Product\ModelBlock\Property\Option();
                        $option->isActive = isset($propertyValuesById[$propertyModel->id]) && ($propertyValuesById[$propertyModel->id] == $optionModel->value);
                        $option->url = $optionModel->product ? $optionModel->product->link : null;
                        $option->shownValue = $optionModel->value;
                        $option->unit = $propertyModel->unit;
                        $option->image = ($propertyModel->isImage && $optionModel->product)
                            ? (string)(new Routing\Product\Media\GetPhoto($optionModel->product->image, $optionModel->product->id, 2))
                            : null
                        ;

                        $property->options[] = $option;
                    }

                    $modelBlock->properties[] = $property;
                }

                if (!(bool)$modelBlock->properties) continue;

                if (0 === $i) {
                    $page->content->product->modelBlock = $modelBlock;
                } else if (1 === $i) {
                    $page->content->product->moreModelBlock = $modelBlock;
                }
            }
        }

        // partner
        try {
            $page->partners = (new Repository\Partial\Partner())->getListForProductCard($request);
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['partner']]);
        }

        // шаблоны mustache
        (new Repository\Template())->setListForPage($page, [
            [
                'id'       => 'tpl-product-slider',
                'name'     => 'partial/product-slider/default',
                'partials' => [
                    'partial/cart/button',
                ],
            ],
            [
                'id'       => 'tpl-product-buyButtonBlock',
                'name'     => 'page/product-card/buttonBlock',
                'partials' => [
                    //'partial/cart/button',
                    //'partial/cart/spinner',
                    //'partial/cart/quickButton',
                ],
            ],
        ]);

        // direct credit
        if ($request->hasCredit) {
            $page->content->product->credit = (new Repository\Partial\DirectCredit())->getObject([
                $productModel->id => $productModel,
            ]);
        }

        if (is_object($page->mailRu)) {
            $page->mailRu->productIds = json_encode([$request->product->id]);
            $page->mailRu->pageType = 'product';
            $page->mailRu->price = $request->product->price;
        }

        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}