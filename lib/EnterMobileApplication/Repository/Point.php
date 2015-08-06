<?php

namespace EnterMobileApplication\Repository;

use Enter\Http;
use EnterAggregator\ConfigTrait;
use EnterModel as Model;

class Point extends \EnterRepository\Point {
    use ConfigTrait;


    /**
     * @param string $groupId
     * @param string $defaultName
     * @return string
     */
    public function getName($groupId, $defaultName) {
        switch ($groupId) {
            case 'pickpoint': // Приходит из метода http://scms.enter.ru/api/point/get
                return 'PickPoint';
            case 'hermes': // Приходит из метода http://scms.enter.ru/api/point/get
                return 'Hermes-DPD';
            default:
                return $defaultName;
        }
    }
}