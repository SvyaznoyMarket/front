<?php

namespace EnterRepository\Product;

use Enter\Http;
use Enter\Util;
use EnterAggregator\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterModel as Model;

class Sorting {
    use ConfigTrait, LoggerTrait;

    /**
     * @return Model\Product\Sorting[]
     */
    public function getObjectList() {
        $applicationName = $this->getConfig()->applicationName;

        $sortings = [];

        $data = Util\Json::toArray(file_get_contents($this->getConfig()->dir . '/data/cms/v2/catalog/sorting.json'));
        foreach ($data as $item) {
            if (isset($item['tags'][0]) && !in_array($applicationName, $item['tags'])) continue;

            $item = array_merge([
                'token'     => null,
                'name'      => null,
                'direction' => null,
            ], $item);

            if (!$item['token'] || !$item['name'] || !$item['direction']) {
                $this->getLogger()->push(['type' => 'error', 'error' => 'Неверный элемент сортировки', 'item' => $item, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['repository']]);
                continue;
            }

            $sorting = new Model\Product\Sorting();
            $sorting->name = $item['name'];
            $sorting->shortName = @$item['shortName'];
            $sorting->token = $item['token'];
            $sorting->direction = $item['direction'];

            $sortings[] = $sorting;
        }

        return $sortings;
    }
}