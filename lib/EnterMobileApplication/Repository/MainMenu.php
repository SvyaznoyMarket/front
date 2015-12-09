<?php

namespace EnterMobileApplication\Repository;

use Enter\Curl\Query;
use EnterAggregator\ConfigTrait;
use EnterAggregator\RouterTrait;
use EnterModel as Model;

class MainMenu extends \EnterRepository\MainMenu {
    use ConfigTrait, RouterTrait;

    /**
     * @param Query $menuListQuery
     * @param Query $categoryListQuery
     * @param bool $returnSecretSale
     * @return Model\MainMenu
     */
    public function getObjectByQuery(Query $menuListQuery, Query $categoryListQuery = null, $returnSecretSale = false) {
        $menu = parent::getObjectByQuery($menuListQuery, $categoryListQuery);

        if ($returnSecretSale) {
            $menu->elements[] = $this->getSecretSaleElement();
        }

        return $menu;
    }

    /**
     * @return Model\MainMenu\Element
     */
    public function getSecretSaleElement() {
        $config = $this->getConfig();
        $mediaUrlPrefix = 'http://' . $config->hostname . ($config->version ? '/' . $config->version : '');

        $element = new \EnterModel\MainMenu\Element();
        $element->type = 'category';
        $element->id = 'secretSale';
        $element->name = 'Секретная распродажа';
        $element->char = '';
        $element->image = '';
        $element->url = '';
        $element->level = 1;
        $element->hasChildren = false;
        // TODO
//        $element->media = new \EnterModel\Media([
//            'content_type' => 'image/png',
//            'provider' => 'image',
//            'tags' => ['logo', 'card'],
//            'sources' => [
//                [
//                    'url' => $mediaUrlPrefix . '/img/payment/logos/original/card.png',
//                    'type' => 'original',
//                    'width' => '',
//                    'height' => '',
//                ],
//            ],
//        ]);

        return $element;
    }
}