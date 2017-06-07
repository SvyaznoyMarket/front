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
    public function getObjectByQuery(Query $menuListQuery, Query $categoryListQuery = null, \EnterModel\Region $region = null, \EnterAggregator\Config $config = null) {
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

        if ($region && $config) {
            $phoneText = $region->id == 14974 ? $config->moscowPhone : $config->phone;
            $phoneUrl = 'tel:' . preg_replace('/[^\d]+/is', '', $phoneText);
        } else {
            $phoneText = '';
            $phoneUrl = '';
        }

        $menu->serviceElements = [
            'delivery' => [
                'link' => '/shops',
                'name' => 'Магазины и самовывоз',
                'iconClass' => 'nav-icon--shops'
            ],
            'user' => [
                'link' => '/private',
                'name' => 'Личный кабинет',
                'iconClass' => 'nav-icon--lk'
            ],
            'feedback' => [
                'link' => 'mailto:feedback@enter.ru',
                'name' => 'Обратная связь',
                'iconClass' => 'nav-icon--callback'
            ],
            'phone' => [
                'link' => $phoneUrl,
                'name' => $phoneText,
                'iconClass' => 'nav-icon--phone'
            ]
        ];
        
        $menu->contentElements = [
            [
                'type' => 'content',
                'id' => 'shops',
                'name' => 'Самовывоз'
            ],
            [
                'type' => 'content',
                'id' => 'delivery',
                'name' => 'Доставка'
            ],
            [
                'type' => 'content',
                'id' => 'how_pay',
                'name' => 'Оплата'
            ]
        ];


//        die(json_encode($menu, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return $menu;
    }
}