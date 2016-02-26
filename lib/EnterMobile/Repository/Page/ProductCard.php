<?php

namespace EnterMobile\Repository\Page;

use EnterAggregator\PriceHelperTrait;
use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\DateHelperTrait;
use EnterAggregator\TranslateHelperTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Partial;
use EnterMobile\Model\Page\ProductCard as Page;

class ProductCard {
    use ConfigTrait, LoggerTrait, RouterTrait, DateHelperTrait, TranslateHelperTrait, TemplateHelperTrait, PriceHelperTrait;

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
        $templateHelper = $this->getTemplateHelper();

        $cartProductButtonRepository = new Repository\Partial\Cart\ProductButton();
        $cartProductReserveButtonRepository = new Repository\Partial\Cart\ProductReserveButton();
        $cartSpinnerRepository = new Repository\Partial\Cart\ProductSpinner();
        $productCardRepository = new Repository\Partial\ProductCard();
        $ratingRepository = new Repository\Partial\Rating();
        $productSliderRepository = new Repository\Partial\ProductSlider();
        $mediaRepository = (new \EnterRepository\Media());

        $product = $request->product;

        // заголовок
        $page->title = $product->name . ' - Enter';

        $page->dataModule = 'product.card';

        // хлебные крошки
        $categories = call_user_func(function() use (&$product) {
            if (!$product->category) return [];

            $ancestors = [];
            $parent = $product->category->parent;
            while ($parent) {
                $ancestors[] = $parent;

                $parent = $parent->parent;
            }

            return array_reverse(array_merge([$product->category], $ancestors));
        });
        $page->breadcrumbBlock = new Model\Page\DefaultPage\BreadcrumbBlock();
        foreach ($categories as $categoryModel) {
            $breadcrumb = new Model\Page\DefaultPage\BreadcrumbBlock\Breadcrumb();
            $breadcrumb->name = $categoryModel->name;
            $breadcrumb->url = $categoryModel->link;
            $page->breadcrumbBlock->breadcrumbs[] = $breadcrumb;
        }

        // содержание
        $page->content->product->name = $product->webName;
        $page->content->product->id = $product->id;
        $page->content->product->ui = $product->ui;
        $page->content->product->namePrefix = $product->namePrefix;
        $page->content->product->article = $product->article;
        $page->content->product->description = $product->description;
        $page->content->product->price = $product->price;
        $page->content->product->shownPrice = $product->price ? $this->getPriceHelper()->format($product->price) : null;
        $page->content->product->oldPrice = $product->oldPrice;
        $page->content->product->shownOldPrice = $product->oldPrice ? $this->getPriceHelper()->format($product->oldPrice) : null;
        $page->content->product->cartButtonBlock = (new Repository\Partial\ProductCard\CartButtonBlock())->getObject($product, null, ['position' => 'product']);
        $page->content->product->slotPartnerOffer = $product->getSlotPartnerOffer();
        $page->content->product->partnerOffer = $product->getPartnerOffer();

        if ($product->brand) {
            $page->content->product->brand = new \EnterMobile\Model\Page\ProductCard\Content\Product\Brand();
            $page->content->product->brand->id = $product->brand->id;
            $page->content->product->brand->name = $product->brand->name;
            $page->content->product->brand->token = $product->brand->token;
            $page->content->product->brand->imageUrl = $mediaRepository->getSourceObjectByList($product->brand->media->photos, 'product', 'original')->url;
        }

        // шильдики
        $page->content->product->labels = [];
        foreach ($product->labels as $label) {
            $viewLabel = new \EnterMobile\Model\Page\ProductCard\Content\Product\Label();
            $viewLabel->id = $label->id;
            $viewLabel->name = $label->name;
            $viewLabel->imageUrl = $mediaRepository->getSourceObjectByList($label->media->photos, '124x38', 'original')->url;
            $page->content->product->labels[] = $viewLabel;
        }

        // доставка товара
        $minPickupPrice = 0;

        if ((bool)$product->deliveries) {
            $pickupDeliveries = $this->getPickupDeliveries($product->deliveries);
            $closestPickupDelivery = $this->getClosestPickup($pickupDeliveries);

            $page->content->product->deliveryBlock = new Page\Content\Product\DeliveryBlock();
            foreach ($product->deliveries as $deliveryModel) {
                if (\EnterModel\Product\Delivery::TOKEN_NOW == $deliveryModel->token) continue;

                $delivery = new Page\Content\Product\DeliveryBlock\Delivery();

                if (\EnterModel\Product\Delivery::TOKEN_STANDARD == $deliveryModel->token) {
                    $delivery->name = 'Доставка';
                } else if (
                    \EnterModel\Product\Delivery::TOKEN_SELF == $deliveryModel->token ||
                    \EnterModel\Product\Delivery::TOKEN_PICKPOINT == $deliveryModel->token ||
                    \EnterModel\Product\Delivery::TOKEN_HERMES == $deliveryModel->token ||
                    \EnterModel\Product\Delivery::TOKEN_EUROSET == $deliveryModel->token
                ) {
                    $delivery->name = 'Самовывоз';
                    $minPickupPrice = ($deliveryModel->price && $deliveryModel->price > $minPickupPrice) ? $deliveryModel->price : $minPickupPrice;
                } else if (\EnterModel\Product\Delivery::TOKEN_NOW == $deliveryModel->token) {
                    $delivery->deliveredAtText = 'Сегодня есть в магазинах';
                } else {
                    continue;
                }

                if (in_array($deliveryModel->token, [
                        \EnterModel\Product\Delivery::TOKEN_STANDARD,
                        \EnterModel\Product\Delivery::TOKEN_SELF,
                        \EnterModel\Product\Delivery::TOKEN_EUROSET,
                        \EnterModel\Product\Delivery::TOKEN_HERMES,
                    ])
                ) {
                    $delivery->priceText = !$deliveryModel->price
                        ? 'бесплатно'
                        : ($this->getPriceHelper()->format($deliveryModel->price) . ' p')
                    ;
                    if ($deliveryModel->nearestDeliveredAt) {
                        $delivery->deliveredAtText = $deliveryModel->nearestDeliveredAt->format('d.m.Y');
                    }
                }

                $delivery->token = $deliveryModel->token;

                if ($delivery->token == \EnterModel\Product\Delivery::TOKEN_STANDARD) {
                    $page->content->product->deliveryBlock->deliveries[] = $delivery;
                }
            }

            /** @var \EnterModel\Product\Delivery $closestPickupDelivery */
            if (isset($closestPickupDelivery) && $closestPickupDelivery) {
                $delivery = new Page\Content\Product\DeliveryBlock\Delivery();
                $delivery->name = 'Самовывоз';
                $delivery->token = $closestPickupDelivery->token;
                $delivery->deliveredAtText = $closestPickupDelivery->nearestDeliveredAt->format('d.m.Y');
                $delivery->priceText = (!$minPickupPrice || $minPickupPrice == 0)
                    ? 'бесплатно'
                    : ($this->getPriceHelper()->format($minPickupPrice) . ' p')
                ;
                $page->content->product->deliveryBlock->deliveries[] = $delivery;
            }

        }

        // состояние магазинов
        if ((bool)$product->shopStates) {
            $page->content->product->shopStateBlock = new Page\Content\Product\ShopStateBlock();
            foreach ($product->shopStates as $shopStateModel) {
                if (!$shopStateModel->shop) continue;

                $shopState = new Page\Content\Product\ShopStateBlock\State();

                $shopState->name = $shopStateModel->shop->name;
                $shopState->address = ($request->region->id !== $shopStateModel->shop->regionId) ? $shopStateModel->shop->name : $shopStateModel->shop->address;
                $shopState->url = $shopStateModel->shop->region
                    ? $router->getUrlByRoute(new Routing\ShopCard\Get($shopStateModel->shop->token, $shopStateModel->shop->region->token))
                    : $router->getUrlByRoute(new Routing\Shop\Index());
                $shopState->regime = $shopStateModel->shop->regime;
                $shopState->isInShowroomOnly = !$shopStateModel->quantity && ($shopStateModel->showroomQuantity > 0);
                $shopState->cartButton = $cartProductReserveButtonRepository->getObject($product, $shopStateModel);
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
                $page->content->product->shopStateBlock->shownCount = 'Забрать сегодня в ' . $stateCount . ' ' . $translateHelper->numberChoice($stateCount, ['магазине', 'магазинах', 'магазинах']);
                //$page->content->product->shopStateBlock->hasOnlyOne = 1 === $stateCount;
            }
        }

        // фотографии товара
        foreach ($product->media->photos as $i => $photoModel) {
            $photo = new Page\Content\Product\Photo();
            $photo->name = $product->name;
            $photo->url = $mediaRepository->getSourceObjectByItem($photoModel, 'product_500')->url;
            $photo->previewUrl = $mediaRepository->getSourceObjectByItem($photoModel, 'product_60')->url;
            $photo->originalUrl = $mediaRepository->getSourceObjectByItem($photoModel, 'product_1500')->url;

            $page->content->product->photos[] = $photo;

            if (0 == $i) {
                $page->content->product->mainPhoto = $photo;
            }
        }

        // характеристики товара
        $groupedPropertyModels = [];
        foreach ($product->properties as $propertyModel) {
            if (!isset($groupedPropertyModels[$propertyModel->groupId])) {
                $groupedPropertyModels[$propertyModel->groupId] = [];
            }

            if ($propertyModel->isInList) {
                $page->content->product->propertiesSummary[$propertyModel->position] = [
                    'name' => $propertyModel->name,
                    'value' => $propertyModel->shownValue,
                    'position' => $propertyModel->position
                ];
            }

            $groupedPropertyModels[$propertyModel->groupId][] = $propertyModel;
        }

        foreach ($product->propertyGroups as $propertyGroupModel) {
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

        // сортировка основных свойств и ограничение на вывод до 5
        ksort($page->content->product->propertiesSummary);
        $page->content->product->propertiesSummary = array_values(array_slice($page->content->product->propertiesSummary, 0, 5));

        // рейтинг товара
        if ($product->rating) {
            $rating = new Partial\Rating();
            $rating->reviewCount = $product->rating->reviewCount;
            $rating->stars = $ratingRepository->getStarList($product->rating->starScore);
            $rating->ratingWord = $this->numberChoiceWithCount($rating->reviewCount, ['отзыв', 'отзыва', 'отзывов']);
            $page->content->product->rating = $rating;
        }

        // состав набора
        $page->content->product->kitBlock = false;
        if ($product->relation && (bool)$product->relation->kits) {
            $page->content->product->kitBlock = new Page\Content\Product\KitBlock();
            $page->content->product->kitBlock->isLocked = $product->isKitLocked;

            $cartProductsById = [];
            $count = 0;
            $sum = 0;
            foreach ($product->relation->kits as $kitProductModel) {
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
                $kit->shownPrice = $kitProductModel->price ? $this->getPriceHelper()->format($kitProductModel->price) : null;
                $kit->shownSum = $kitProductModel->price ? $this->getPriceHelper()->format($kitProductModel->price * $kitProductModel->kitCount) : null;
                if (isset($kitProductModel->media->photos[0])) {
                    $kit->photoUrl = $mediaRepository->getSourceObjectByItem($kitProductModel->media->photos[0], 'product_500')->url;
                }

                if (isset($kitProductModel->deliveries[0])) {
                    /** @var \DateTime|null $deliveredDate */
                    $deliveredDate = $kitProductModel->deliveries[0]->nearestDeliveredAt ?: null;
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
                    Repository\Partial\Cart\ProductButton::getId($product->id, false),
                    false,
                    $router->getUrlByRoute(new Routing\Product\QuantityAvailabilityList())
                );

                $kit->isHidden = !$kitProductModel->kitCount;

                $page->content->product->kitBlock->products[] = $kit;
            }

            $page->content->product->kitBlock->shownSum = $this->getPriceHelper()->format($sum);
            $page->content->product->kitBlock->shownQuantity = 'Итого за ' . $count . ' ' . $translateHelper->numberChoice($count, ['предмет', 'предмета', 'предметов']);
            $page->content->product->kitBlock->cartButton = $cartProductButtonRepository->getListObject(
                array_reverse($product->relation->kits),
                $cartProductsById,
                $product->id,
                false,
                '+' // quantitySign
            );
            $page->content->product->kitBlock->resetDataValue = $page->content->product->kitBlock->cartButton->dataValue;
        }

        // аксессуары товара
        if ((bool)$product->relation->accessories) {
            $page->content->product->accessorySlider = $productSliderRepository->getObject('accessorySlider');
            $page->content->product->accessorySlider->count = count($product->relation->accessories);
            foreach ($product->relation->accessories as $accessoryModel) {
                $page->content->product->accessorySlider->productCards[] = $productCardRepository->getObject($accessoryModel, $cartProductButtonRepository->getObject($accessoryModel, null, true, true, ['position' => 'listing']));
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

        // избранное
        if (isset($product->favorite) && !empty($product->favorite)) {
            foreach ($product->favorite as $key => $favoriteProductUi) {
                if ($product->ui == $favoriteProductUi) $page->content->product->isFavorite = true;
            }
        }

        // рекомендации товара
        $recommendListUrl = $router->getUrlByRoute(new Routing\Product\GetRecommendedList($product->id));
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
        if ((bool)$product->reviews) {
            $page->content->product->reviewBlock = new Page\Content\Product\ReviewBlock();
            foreach ($product->reviews as $reviewModel) {
                $review = new Partial\ProductReview();
                $review->author = $reviewModel->author;
                $review->createdAt = $reviewModel->createdAt ? $dateHelper->dateToRu($reviewModel->createdAt): null;
                $review->extract = $reviewModel->extract;
                $review->cons = $reviewModel->cons;
                $review->pros = $reviewModel->pros;
                $review->stars = $ratingRepository->getStarList($reviewModel->starScore);

                $page->content->product->reviewBlock->reviews[] = $review;
            }

            if ($product->rating && ($product->rating->reviewCount > $config->productReview->itemsInCard)) {

                $page->content->product->reviewBlock->moreLink = new Partial\Link();
                $page->content->product->reviewBlock->moreLink->name = 'Еще отзывы';

                //$page->content->product->reviewBlock->moreLink = null;

                $page->content->product->reviewBlock->url = $router->getUrlByRoute(new Routing\Product\Review\GetList($product->id));
                $page->content->product->reviewBlock->dataValue = $templateHelper->json(['page' => 2]);
            }
        }

        // модели товара
        call_user_func(function() use($product, &$page) {
            if (!$product->model || !$product->model->property || !$product->model->property->options) {
                return;
            }

            $page->content->product->modelBlock = new Page\Content\Product\ModelBlock();

            $modelBlockProperty = new Page\Content\Product\ModelBlock\Property();
            $modelBlockProperty->name = $product->model->property->name;
            foreach ($product->model->property->options as $optionModel) {
                $modelBlockOption = new Page\Content\Product\ModelBlock\Property\Option();
                $modelBlockOption->isActive = $optionModel->product && $optionModel->product->ui === $product->ui;
                $modelBlockOption->url = $optionModel->product ? $optionModel->product->link : null;
                $modelBlockOption->shownValue = $optionModel->value;
                $modelBlockProperty->options[] = $modelBlockOption;

                if ($modelBlockOption->isActive) {
                    $page->content->product->modelBlock->shownValue = $modelBlockOption->shownValue;
                }
            }

            $page->content->product->modelBlock->properties[] = $modelBlockProperty;
        });

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
                'name'     => 'partial/product-slider/new-default',
                'partials' => [
                    'partial/cart/button-new',
                ],
            ],
            [
                'id'       => 'tpl-product-buyButtonBlock',
                'name'     => 'page/product-card-new/buttonBlock',
                'partials' => [
                    'partial/cart/button-new',
                    //'partial/cart/spinner',
                    //'partial/cart/quickButton',
                ],
            ],
            [
                'id'       => 'tpl-product-addReviewForm',
                'name'     => 'partial/add-review-form',
                'partials' => [
                    //'partial/cart/button',
                    //'partial/cart/spinner',
                    //'partial/cart/quickButton',
                ],
            ],
            [
                'id'       => 'tpl-product-slider-large',
                'name'     => 'partial/product-slider/large-images',
                'partials' => [
                    'partial/cart/button-new',
                    'partial/rating/star-list'
                ],
            ]
        ]);

        // direct credit
        if ($request->hasCredit) {
            $page->content->product->credit = (new Repository\Partial\DirectCredit())->getObject([
                $product->id => $product,
            ]);
        }

        if (is_object($page->mailRu)) {
            $page->mailRu->productIds = json_encode([$request->product->id]);
            $page->mailRu->pageType = 'product';
            $page->mailRu->price = $request->product->price;
        }

        //die(json_encode($page->content->product, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function numberChoice($number, array $choices) {
        $cases = [2, 0, 1, 1, 1, 2];

        return $choices[ ($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)]];
    }

    /**
     * @param int   $number  Например: 1, 43, 112
     * @param array $choices Например: ['отзыв', 'отзыва', 'отзывов']
     * @param string $wordsBetween Например 'прекрасных'
     * @return string '3 прекрасных отзыва'
     */
    private function numberChoiceWithCount($number, array $choices, $wordsBetween = '') {
        return preg_replace('/\s+/', ' ', $number.' '.$wordsBetween.' '.$this->numberChoice($number, $choices));
    }

    /**
     * @param \EnterModel\Product\Delivery[] $deliveries
     * @return \EnterModel\Product\Delivery[]
     */
    private function getPickupDeliveries(array $deliveries) {
        $pickupDeliveries = array_filter($deliveries, function ($delivery) {
            return $delivery->token == \EnterModel\Product\Delivery::TOKEN_SELF
            || $delivery->token == \EnterModel\Product\Delivery::TOKEN_PICKPOINT
            || $delivery->token == \EnterModel\Product\Delivery::TOKEN_HERMES
            || $delivery->token == \EnterModel\Product\Delivery::TOKEN_EUROSET;
        });

        return $pickupDeliveries;
    }

    /**
     * @param \EnterModel\Product\Delivery[] $pickupDeliveries
     * @return \EnterModel\Product\Delivery[]
     */
    private function getClosestPickup(array $pickupDeliveries) {
        $pickupDate = null;
        $minDelivery = null;

        foreach ($pickupDeliveries as $pickupDelivery) {
            if ($pickupDelivery->nearestDeliveredAt < $pickupDate || $pickupDate === null) {
                $pickupDate = $pickupDelivery->nearestDeliveredAt;
                $minDelivery = $pickupDelivery;
            }
        }

        return $minDelivery;
    }

}