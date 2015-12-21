<?php

namespace EnterMobile\Controller\User\Subscribe;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterAggregator\DebugContainerTrait;
use EnterMobile\Controller\SecurityTrait;
use EnterMobile\Repository;
use EnterQuery as Query;
use EnterMobile\Model\Page\User\Subscribe\Index as Page;

class Index {
    use
        SecurityTrait,
        ConfigTrait,
        LoggerTrait,
        CurlTrait,
        MustacheRendererTrait,
        DebugContainerTrait,
        SessionTrait;

    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();
        // ид региона
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        // запрос региона
        $regionQuery = new Query\Region\GetItemById($regionId);
        $curl->prepare($regionQuery);

        $userToken = $this->getUserToken($request);

        // запрос пользователя
        $userItemQuery = (new \EnterMobile\Repository\User())->getQueryByHttpRequest($request);
        $curl->prepare($userItemQuery);

        // запрос каналов подписки
        $channelQuery = new Query\Subscribe\Channel\GetList();
        $curl->prepare($channelQuery);

        $curl->execute();

        // пользователь
        $user = $this->getUser($userItemQuery);

        // регион
        $region = (new \EnterRepository\Region())->getObjectByQuery($regionQuery);

        $cart = (new \EnterRepository\Cart())->getObjectByHttpSession($this->getSession(), $config->cart->sessionKey);
        $cartItemQuery = (new \EnterMobile\Repository\Cart())->getPreparedCartItemQuery($cart, $region->id);
        $cartProductListQuery = (new \EnterMobile\Repository\Cart())->getPreparedCartProductListQuery($cart, $region->id);

        // каналы подписок
        if ($error = $channelQuery->getError()) {
            throw $error;
        }
        /** @var \EnterModel\Subscribe\Channel[] $channelsById */
        $channelsById = [];
        foreach ($channelQuery->getResult() as $item) {
            if (empty($item['id'])) continue;

            $channel = new \EnterModel\Subscribe\Channel($item);
            $channelsById[$channel->id] = $channel;
        }

        // запрос подписок пользователя
        $subscribeQuery = new Query\Subscribe\GetListByUserToken($userToken);
        $curl->prepare($subscribeQuery);

        $curl->execute();

        (new \EnterRepository\Cart())->updateObjectByQuery($cart, $cartItemQuery, $cartProductListQuery);

        // подписки пользователя
        if ($error = $subscribeQuery->getError()) {
            throw $error;
        }
        $subscriptionsGroupedByChannel = [];
        foreach ($subscribeQuery->getResult() as $item) {
            if (empty($item['channel_id'])) continue;

            $subscription = new \EnterModel\Subscribe($item);
            if (!$subscription->channelId) continue;

            // пропустить подписки, у которых email не совпадает с email-ом пользователя
            if (('email' === $subscription->type) && $user->email && ($user->email !== $subscription->email)) continue;

            $subscriptionsGroupedByChannel[$subscription->channelId][] = $subscription;
        }

        $userMenu = (new \EnterRepository\UserMenu())->getItems($userToken, $user);

        //запрос для получения страницы
        $pageRequest = new Repository\Page\User\Subscribe\Request();
        $pageRequest->httpRequest = $request;
        $pageRequest->region = $region;
        $pageRequest->user = $user;
        $pageRequest->cart = $cart;
        $pageRequest->userMenu = $userMenu;
        $pageRequest->subscriptionsGroupedByChannel = $subscriptionsGroupedByChannel;
        $pageRequest->channelsById = $channelsById;

        $page = new Page();
        (new Repository\Page\User\Subscribe())->buildObjectByRequest($page, $pageRequest);
        if ($config->debugLevel) $this->getDebugContainer()->page = $page;

        // рендер
        $renderer = $this->getRenderer();

        if ($request->isXmlHttpRequest()) {
            $response = new Http\JsonResponse([
                'content' => $renderer->render('page/private/subscribe/content', $page->content),
            ]);
        } else {
            $renderer->setPartials([
                'content' => 'page/private/subscribe'
            ]);
            $content = $renderer->render('layout/footerless', $page);

            $response = new Http\Response($content);
        }

        return $response;
    }
}