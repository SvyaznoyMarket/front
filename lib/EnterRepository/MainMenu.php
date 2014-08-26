<?php

namespace EnterRepository;

use Enter\Curl\Query;
use EnterAggregator\ConfigTrait;
use EnterAggregator\RouterTrait;
use EnterModel as Model;

class MainMenu {
    use ConfigTrait, RouterTrait;

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

        $categoryItemsById = [];
        // индексирование данных категорий по id
        $walkByCategoryData = function(&$categoryData) use (&$categoryItemsById, &$walkByCategoryData) {
            $categoryItem = null;
            foreach ($categoryData as &$categoryItem) {
                if (isset($categoryItem['id'])) $categoryItem['id'] = (string)$categoryItem['id'];
                if (isset($categoryItem['root_id'])) $categoryItem['root_id'] = (string)$categoryItem['root_id'];

                $categoryItemsById[$categoryItem['id']] = $categoryItem;

                if (isset($categoryItem['children'][0])) {
                    $walkByCategoryData($categoryItem['children']);
                }
            }
            unset($categoryItem);
        };
        $walkByCategoryData($categoryData);

        $walkByMenuElementItem = function($elementItems, Model\MainMenu\Element $parentElement = null) use (&$menu, &$walkByMenuElementItem, &$categoryItemsById) {
            foreach ($elementItems as $elementItem) {
                if (isset($elementItem['disabled']) && (true === $elementItem['disabled'])) {
                    continue;
                }

                $element = null;

                $source = !empty($elementItem['source']['type']) ? ($elementItem['source'] + ['type' => null, 'id' => null]) : null;
                if ($source) {
                    $id = $source['id'];

                    if (('category-get' == $source['type']) && !empty($id)) {
                        $categoryItem = isset($categoryItemsById[$id]) ? $categoryItemsById[$id] : null;

                        $element = new Model\MainMenu\Element($elementItem);
                        $element->type = 'category';
                        $element->id = (string)$categoryItem['id'];
                        if (!$element->name) {
                            $element->name = (string)$categoryItem['name'];
                        }
                        $element->url = rtrim((string)$categoryItem['link'], '/');
                    } else if (('category-tree' == $source['type']) && !empty($id)) {
                        $elementItems = [];
                        $categoryItem = null;
                        foreach (isset($categoryItemsById[$id]['children'][0]) ? $categoryItemsById[$id]['children'] : [] as $categoryItem) {
                            $elementItems[] = [
                                'source' => [
                                    'type' => 'category-get',
                                    'id'   => $categoryItem['id'],
                                ],
                            ];
                        }
                        unset($categoryItem);

                        $walkByMenuElementItem($elementItems, $parentElement);
                    } else if (('slice' == $source['type']) && !empty($source['url'])) {
                        $element = new Model\MainMenu\Element($elementItem);
                        $element->type = 'slice';
                        $element->url = '/slices/' . $source['url']; // FIXME
                    }
                } else {
                    $element = new Model\MainMenu\Element($elementItem);
                }

                if (!$element) continue;

                $element->class .= ((bool)$element->class ? ' ' : '') . 'mId' . md5(json_encode($element));

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

        //die(json_encode($menu->elements, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return $menu;
    }
}