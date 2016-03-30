<?php

namespace EnterMobile\Controller\User\Address;

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
use EnterMobile\Model\Page\User\Address\Index as Page;

class Index {
    use SecurityTrait,
        ConfigTrait,
        LoggerTrait,
        CurlTrait,
        MustacheRendererTrait,
        DebugContainerTrait,
        SessionTrait;

    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();
        $session = $this->getSession();
        // ид региона
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        // запрос региона
        $regionQuery = new Query\Region\GetItemById($regionId);
        $curl->prepare($regionQuery);

        $userToken = $this->getUserToken($session, $request);

        // запрос пользователя
        $userItemQuery = (new \EnterMobile\Repository\User())->getQueryBySessionAndHttpRequest($session, $request);
        $curl->prepare($userItemQuery);

        $curl->execute();

        // регион
        $region = (new \EnterRepository\Region())->getObjectByQuery($regionQuery);
        // пользователь
        $user = $this->getUser($userItemQuery);

        $cart = (new \EnterRepository\Cart())->getObjectByHttpSession($this->getSession(), $config->cart->sessionKey);
        $cartItemQuery = (new \EnterMobile\Repository\Cart())->getPreparedCartItemQuery($cart, $region->id);
        $cartProductListQuery = (new \EnterMobile\Repository\Cart())->getPreparedCartProductListQuery($cart, $region->id);

        // запрос адресов
        $addressQuery = new Query\User\Address\GetListByUserUi($user->ui);
        $curl->prepare($addressQuery);

        $curl->execute();

        (new \EnterRepository\Cart())->updateObjectByQuery($cart, $cartItemQuery, $cartProductListQuery);

        if ($error = $addressQuery->getError()) {
            throw $error;
        }

        // адреса
        /** @var \EnterModel\Address[] $addresses */
        $addresses = [];
        foreach ($addressQuery->getResult() as $item) {
            if (empty($item['id'])) continue;
            $addresses[] = new \EnterModel\Address($item);
        }

        /** @var \EnterModel\Region[] $regionsById */
        $regionsById = [];
        // ид регионов
        $regionIds = [];
        foreach ($addresses as $address) {
            if (!$address->regionId) continue;
            $regionIds[] = $address->regionId;
        }
        $regionIds = array_values(array_unique($regionIds));
        if ((1 === count($regionIds)) && ($regionIds[0] === $region->id)) {
            $regionsById[$region->id] = $region;
        } else if ($regionIds) {
            $regionQuery = new Query\Region\GetListByIdList($regionIds);
            $curl->prepare($regionQuery);

            $curl->execute();

            foreach ($regionQuery->getResult() as $item) {
                if (empty($item['id'])) continue;
                $iRegion = new \EnterModel\Region($item);

                $regionsById[$iRegion->id] = $iRegion;
            }

        }

        $userMenu = (new \EnterRepository\UserMenu())->getItems($userToken, $user);

        //запрос для получения страницы
        $pageRequest = new Repository\Page\User\Address\Request();
        $pageRequest->httpRequest = $request;
        $pageRequest->region = $region;
        $pageRequest->user = $user;
        $pageRequest->cart = $cart;
        $pageRequest->userMenu = $userMenu;
        $pageRequest->addresses = $addresses;
        $pageRequest->regionsById = $regionsById;

        $page = new Page();
        (new Repository\Page\User\Address())->buildObjectByRequest($page, $pageRequest);
        if ($config->debugLevel) $this->getDebugContainer()->page = $page;

        // рендер
        $renderer = $this->getRenderer();

        if ($request->isXmlHttpRequest()) {
            $response = new Http\JsonResponse([
                'content' => $renderer->render('page/private/address/content', $page->content),
            ]);
        } else {
            $renderer->setPartials([
                'content' => 'page/private/address'
            ]);
            $content = $renderer->render('layout/footerless', $page);

            $response = new Http\Response($content);
        }

        return $response;
    }
}