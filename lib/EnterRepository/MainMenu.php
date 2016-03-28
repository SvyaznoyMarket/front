<?php

namespace EnterRepository;

use Enter\Curl\Query;
use EnterAggregator\ConfigTrait;
use EnterAggregator\RouterTrait;
use EnterModel as Model;

class MainMenu {
    use ConfigTrait, RouterTrait;

    /**
     * @param int $level
     * @return \EnterQuery\Product\Category\GetTree
     */
    public function getCategoryTreeQuery($level) {
        return new \EnterQuery\Product\Category\GetTree(
            null,
            $level,
            false,
            null,
            true
        );
    }

    /**
     * @param Query $menuListQuery
     * @param Query $categoryListQuery
     * @return Model\MainMenu
     */
    public function getObjectByQuery(Query $menuListQuery, Query $categoryListQuery = null) {
        $menu = new Model\MainMenu();

        try {
            $menuData = $menuListQuery->getResult();
            if (!(bool)$menuData) {
                throw new \Exception('Пустое главное меню');
            }
        } catch (\Exception $e) {
            $menuData = json_decode(file_get_contents($this->getConfig()->dir . '/data/cms/v2/main-menu.json'), true);

            //trigger_error($e, E_USER_ERROR);
        }
        //$menuData = json_decode(file_get_contents($this->getConfig()->dir . '/data/cms/v2/main-menu.json'), true);
        $categoryData = $categoryListQuery->getResult();

        $categoryItemsByUi = [];
        // индексирование данных категорий по ui
        $walkByCategoryData = function(&$categoryData) use (&$categoryItemsByUi, &$walkByCategoryData) {
            $categoryItem = null;
            foreach ($categoryData as &$categoryItem) {
                if (isset($categoryItem['uid'])) $categoryItem['uid'] = (string)$categoryItem['uid'];
                if (isset($categoryItem['root_id'])) $categoryItem['root_id'] = (string)$categoryItem['root_id'];

                $categoryItemsByUi[$categoryItem['uid']] = $categoryItem;

                if (isset($categoryItem['children'][0])) {
                    $walkByCategoryData($categoryItem['children']);
                }
            }
            unset($categoryItem);
        };
        $walkByCategoryData($categoryData);

        $walkByMenuElementItem = function($elementItems, Model\MainMenu\Element $parentElement = null) use (&$menu, &$walkByMenuElementItem, &$categoryItemsByUi) {
            foreach ($elementItems as $elementItem) {
                if (isset($elementItem['disabled']) && (true === $elementItem['disabled'])) {
                    continue;
                }

                $element = null;

                $source = !empty($elementItem['source']['type']) ? ($elementItem['source'] + ['type' => null, 'uid' => null]) : null;
                if ($source) {
                    $ui = $source['uid'];

                    if (('category-get' == $source['type']) && !empty($ui)) {
                        $categoryItem = isset($categoryItemsByUi[$ui]) ? $categoryItemsByUi[$ui] : null;

                        $element = new Model\MainMenu\Element($elementItem);
                        $element->type = 'category';
                        $element->id = (string)$categoryItem['id'];
                        if (!$element->id && isset($elementItem['source']['id'])) {
                            $element->id = (string)$elementItem['source']['id'];
                        }

                        if (!$element->name) {
                            $element->name = (string)$categoryItem['name'];
                        }
                        $element->url = rtrim((string)$categoryItem['link'], '/');
                    } else if (('category-tree' == $source['type']) && !empty($ui)) {
                        $elementItems = [];
                        $categoryItem = null;
                        foreach (isset($categoryItemsByUi[$ui]['children'][0]) ? $categoryItemsByUi[$ui]['children'] : [] as $categoryItem) {
                            $elementItems[] = [
                                'source' => [
                                    'type'  => 'category-get',
                                    'uid'   => $categoryItem['uid'],
                                    'id'    => $categoryItem['id'],
                                ],
                            ];
                        }
                        unset($categoryItem);

                        $walkByMenuElementItem($elementItems, $parentElement);
                    } else if (('slice' == $source['type']) && !empty($source['url'])) {
                        $element = new Model\MainMenu\Element($elementItem);
                        $element->type = 'slice';
                        $element->id = $source['url'];
                        $element->url = '/slices/' . $source['url']; // FIXME
                    }
                } else {
                    $element = new Model\MainMenu\Element($elementItem);
                }

                if (!$element) continue;


                if (isset($elementItem['children'][0])) {
                    $walkByMenuElementItem($elementItem['children'], $element);
                }

                $element->level = $parentElement ? ($parentElement->level + 1) : 1;
                $element->hasChildren = (bool)$element->children;

                if ($parentElement) {
                    $parentElement->children[] = $element;
                } else {
                    $menu->elements[] = $element;
                }
            }
        };
        $walkByMenuElementItem($menuData['item']);

        $menu->serviceElements = [
            'deliveryShops' => [
                'link' => '/shops',
                'name' => 'Точки самовывоза'
            ],
            'self' => [
                'link' => '/delivery_types#delivr_self',
                'name' => 'Самовывоз'
            ],
            'delivery' => [
                'link' => '/how_get_order',
                'name' => 'Доставка'
            ],
            'payment' => [
                'link' => '/how_pay',
                'name' => 'Оплата'
            ]
        ];

        $menu->enterInfo = [
            'feedback' => [
                'link' => 'mailto:feedback@enter.ru',
                'name' => 'Обратная связь'
            ],
            'phone' => [
                'link' => 'tel:+74957750006',
                'name' => '+7 495 775-00-06'
            ],
            'companyInfo' => [
                'link' => '/about_company',
                'name' => 'О компании'
            ]
        ];

//        die(json_encode($menu, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return $menu;
    }
}