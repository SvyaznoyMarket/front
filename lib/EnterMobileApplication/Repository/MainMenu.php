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
    public function getObjectByQuery(Query $menuListQuery, Query $categoryListQuery = null, \EnterModel\Region $region = null, \EnterAggregator\Config $config = null, $returnSecretSale = false) {
        $menu = parent::getObjectByQuery($menuListQuery, $categoryListQuery, $region, $config);

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
        $mediaUrlPrefix = '//' . $config->hostname . ($config->version ? '/' . $config->version : '');

        $element = new \EnterModel\MainMenu\Element();
        $element->type = 'category';
        $element->id = 'secretSale';
        $element->name = 'Секретная распродажа';
        $element->char = '';
        $element->image = '';
        $element->url = '';
        $element->level = 1;
        $element->hasChildren = false;

        $element->media[] = new \EnterModel\Media([
            'content_type' => 'image/png',
            'provider' => 'image',
            'tags' => [
                'mobile-app',
                'mdpi',
                'android',
                'app-mobile'
            ],
            'sources' => [
                [
                    'width' => '40',
                    'height' => '40',
                    'type' => 'original',
                    'url' => $mediaUrlPrefix . '/img/menu/40x40/secretSale.png',
                ],
            ],
        ]);
        
        $element->media[] = new \EnterModel\Media([
            'content_type' => 'image/png',
            'provider' => 'image',
            'tags' => [
                'mobile-app',
                'hdpi',
                'android',
                'app-mobile'
            ],
            'sources' => [
                [
                    'width' => '125',
                    'height' => '125',
                    'type' => 'original',
                    'url' => $mediaUrlPrefix . '/img/menu/125x125/secretSale.png',
                ],
            ],
        ]);

        $element->media[] = new \EnterModel\Media([
            'content_type' => 'image/png',
            'provider' => 'image',
            'tags' => [
                'mobile-app',
                'xhdpi',
                'android',
                'app-mobile'
            ],
            'sources' => [
                [
                    'width' => '250',
                    'height' => '250',
                    'type' => 'original',
                    'url' => $mediaUrlPrefix . '/img/menu/250x250/secretSale.png',
                ],
            ],
        ]);

        $element->media[] = new \EnterModel\Media([
            'content_type' => 'image/png',
            'provider' => 'image',
            'tags' => [
                'landscape',
                'app-mobile'
            ],
            'sources' => [
                [
                    'width' => '125',
                    'height' => '125',
                    'type' => 'original',
                    'url' => $mediaUrlPrefix . '/img/menu/125x125/secretSale.png',
                ],
            ],
        ]);

        $element->media[] = new \EnterModel\Media([
            'content_type' => 'image/png',
            'provider' => 'image',
            'tags' => [
                'portait',
                'app-mobile'
            ],
            'sources' => [
                [
                    'width' => '125',
                    'height' => '125',
                    'type' => 'original',
                    'url' => $mediaUrlPrefix . '/img/menu/125x125/secretSale.png',
                ],
            ],
        ]);
        
        return $element;
    }
}