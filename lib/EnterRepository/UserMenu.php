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

    public function getItems() {
        $menuItems = [
            'orders' => [
                'token' => 'orders',
                'name' => 'Заказы',
                'url' => '/private/orders',
                'isActive' => false,
                'count' => false,
                'image' => 'i-orders.png',
            ],
            'subscribes' => [
                'token' => 'subscribes',
                'name' => 'Подписки',
                'url' => '/private/subscribes',
                'isActive' => false,
                'count' => false,
                'image' => 'i-subscription.png',
            ],
            'favorit' => [
                'token' => 'favorit',
                'name' => 'Избранное',
                'url' => '/private/favorites',
                'isActive' => false,
                'count' => false,
                'image' => 'i-favorit.png',
            ],
            'addresses' => [
                'token' => 'addresses',
                'name' => 'Избранное',
                'url' => '/private/addresses',
                'isActive' => false,
                'count' => false,
                'image' => 'i-address.png',
            ],
            'enterprize' => [
                'token' => 'enterprize',
                'name' => 'Фишки EnterPrize',
                'url' => '/private/enterprize',
                'isActive' => false,
                'count' => false,
                'image' => 'i-ep.png',
            ],
        ];

        return $menuItems;
    }
}