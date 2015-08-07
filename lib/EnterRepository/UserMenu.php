<?php

namespace EnterRepository;

use Enter\Http;
use Enter\Curl\Query;
use Enter\Logging\Logger;
use EnterAggregator\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterModel as Model;

class UserMenu {
    use ConfigTrait,
        LoggerTrait;

    /** @var Logger */
    protected $logger;

    public function __construct() {
        $this->logger = $this->getLogger();
    }

    public function getMenuItems() {
        $menuItems = [
            [
                'token' => 'orders',
                'name' => 'Заказы',
                'url' => '/private/orders',
                'isActive' => false
            ],
            [
                'token' => 'edit',
                'name' => 'Личные данные',
                'url' => '/private/edit',
                'isActive' => false
            ],
            [
                'token' => 'password',
                'name' => 'Изменение пароля',
                'url' => '/private/password',
                'isActive' => false
            ],
            [
                'token' => 'enterprize',
                'name' => 'Фишки EnterPrize',
                'url' => '/private/enterprize',
                'isActive' => false
            ]
        ];

        return $menuItems;
    }
}