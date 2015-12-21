<?php

namespace EnterRepository;

use Enter\Http;
use Enter\Logging\Logger;
use EnterAggregator\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\CurlTrait;
use EnterModel as Model;
use EnterQuery as Query;

class UserMenu {
    use
        ConfigTrait,
        LoggerTrait,
        CurlTrait;

    /** @var Logger */
    protected $logger;

    public function __construct() {
        $this->logger = $this->getLogger();
    }

    public function getItems($userToken, Model\User $user) {
        $curl = $this->getCurl();

        $items = [
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
                'name' => 'Адреса',
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

        // подготовка количества заказов
        $orderQuery = new Query\Order\GetListByUserToken($userToken, 0, 0);
        $curl->prepare($orderQuery);

        // запрос подписок пользователя
        $subscribeQuery = new Query\Subscribe\GetListByUserToken($userToken);
        $curl->prepare($subscribeQuery);

        // запрос избранного
        $favoriteQuery = new Query\User\Favorite\GetListByUserUi($user->ui);
        $curl->prepare($favoriteQuery);

        // запрос адресов
        $addressQuery = new Query\User\Address\GetListByUserUi($user->ui);
        $curl->prepare($addressQuery);

        // список купонов
        $couponListQuery = new Query\Coupon\GetListByUserToken($userToken);
        $curl->prepare($couponListQuery);

        // список лимитов серий купонов
        $seriesLimitListQuery = new Query\Coupon\Series\GetLimitList();
        $curl->prepare($seriesLimitListQuery);

        // список серий купонов
        $seriesListQuery = new Query\Coupon\Series\GetList(/*$user->isEnterprizeMember ? '1' : null*/null);
        $curl->prepare($seriesListQuery);

        $curl->execute();

        // подписки
        $subscriptionsGroupedByChannel = [];
        foreach ($subscribeQuery->getResult() as $item) {
            if (empty($item['channel_id'])) continue;

            $subscription = new \EnterModel\Subscribe($item);
            if (!$subscription->channelId) continue;

            // пропустить подписки, у которых email не совпадает с email-ом пользователя
            if (('email' === $subscription->type) && $user->email && ($user->email !== $subscription->email)) continue;

            $subscriptionsGroupedByChannel[$subscription->channelId][] = $subscription;
        }

        // купоны
        $usedSeriesIds = [];
        foreach ((new \EnterRepository\Coupon())->getObjectListByQuery($couponListQuery) as $coupon) {
            $usedSeriesIds[] = $coupon->seriesId;
        }

        $almostReadyCoupons = array_values(
            array_filter( // фильрация серий купонов
                (new \EnterRepository\Coupon\Series())->getObjectListByQuery($seriesListQuery, $seriesLimitListQuery),
                function(Model\Coupon\Series $series) use (&$usedSeriesIds) {
                    return in_array($series->id, $usedSeriesIds); // только те серии купонов, которые есть у ранее полученых купонов
                }
            )
        );

        $coupons = [];
        $now = time();
        foreach ($almostReadyCoupons as $coupon) {
            if ($now > strtotime($coupon->endAt)) continue;

            $coupons[] = $coupon;
        }


        if (isset($items['orders']) && !$orderQuery->getError()) {
            try {
                $items['orders']['count'] = $orderQuery->getResult()['total'];
            } catch (\Exception $e) {}
        }
        if (isset($items['subscribes']) && !$subscribeQuery->getError()) {
            try {
                $items['subscribes']['count'] = count($subscriptionsGroupedByChannel);
            } catch (\Exception $e) {}
        }
        if (isset($items['favorit']) && !$favoriteQuery->getError()) {
            try {
                $items['favorit']['count'] = count($favoriteQuery->getResult()['products']);
            } catch (\Exception $e) {}
        }
        if (isset($items['addresses']) && !$addressQuery->getError()) {
            try {
                $items['addresses']['count'] = count($addressQuery->getResult());
            } catch (\Exception $e) {}
        }
        if (isset($items['enterprize'])) {
            try {
                $items['enterprize']['count'] = count($coupons);
            } catch (\Exception $e) {}
        }

        return $items;
    }
}