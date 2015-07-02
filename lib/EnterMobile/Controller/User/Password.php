<?php

namespace EnterMobile\Controller\User;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterAggregator\DebugContainerTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\CurlTrait;
use EnterMobile\Repository;
use EnterQuery as Query;
use EnterMobile\Model\Page\User\ResetPassword as Page;

class Password {

    use ConfigTrait, CurlTrait, SessionTrait, MustacheRendererTrait, DebugContainerTrait;

    public function execute(Http\Request $request) {
        $curl = $this->getCurl();
        $session = $this->getSession();
        $config = $this->getConfig();
        $messageRepository = new \EnterRepository\Message();

        // регион
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);
        $regionQuery = new Query\Region\GetItemById($regionId);
        $curl->prepare($regionQuery);
        $curl->execute();
        $region = (new \EnterRepository\Region())->getObjectByQuery($regionQuery);
        // /регион

        // меню
        $categoryTreeQuery = (new \EnterRepository\MainMenu())->getCategoryTreeQuery(1);
        $curl->prepare($categoryTreeQuery);
        $mainMenuQuery = new Query\MainMenu\GetItem();
        $curl->prepare($mainMenuQuery);
        $curl->execute();
        $mainMenu = (new \EnterRepository\MainMenu())->getObjectByQuery($mainMenuQuery, $categoryTreeQuery);
        // /меню

        // запрос пользователя
        $userItemQuery = (new \EnterMobile\Repository\User())->getQueryByHttpRequest($request);
        if ($userItemQuery) {
            $curl->prepare($userItemQuery);
        }

        $curl->execute();

        $cart = (new \EnterRepository\Cart())->getObjectByHttpSession($this->getSession(), $config->cart->sessionKey);
        $cartItemQuery = (new \EnterMobile\Repository\Cart())->getPreparedCartItemQuery($cart, $region->id);
        $cartProductListQuery = (new \EnterMobile\Repository\Cart())->getPreparedCartProductListQuery($cart, $region->id);

        $curl->execute();

        (new \EnterRepository\Cart())->updateObjectByQuery($cart, $cartItemQuery, $cartProductListQuery);

        $pageRequest = new Repository\Page\User\PasswordReset\Request();
        $pageRequest->region = $region;
        $pageRequest->mainMenu = $mainMenu;
        $pageRequest->httpRequest = $request;
        $pageRequest->user = (new \EnterMobile\Repository\User())->getObjectByQuery($userItemQuery);
        $pageRequest->cart = $cart;

        $pageRequest->formErrors = array_map(
                                        function(\EnterModel\Message $message) { return $message->name; },
                                        $messageRepository->getObjectListByHttpSession('changePassword.error', $session)
                                    );

        $page = new Page();
        (new Repository\Page\User\PasswordReset())->buildObjectByRequest($page, $pageRequest);

        // рендер
        $renderer = $this->getRenderer();
        $renderer->setPartials([
            'content' => 'page/private/password'
        ]);

        $content = $renderer->render('layout/default', $page);

        return new Http\Response($content);
    }
}