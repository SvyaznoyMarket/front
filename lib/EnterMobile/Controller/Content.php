<?php
namespace EnterMobile\Controller;

use Enter\Http;
use EnterAggregator\CurlTrait;
use EnterAggregator\DebugContainerTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterAggregator\SessionTrait;
use EnterMobile\ConfigTrait;
use EnterQuery as Query;
use EnterMobile\Model\Page\Content as Page;

class Content {
    use ConfigTrait, CurlTrait, MustacheRendererTrait, DebugContainerTrait, SessionTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();

        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);
        $contentToken = $request->query['contentToken'];

        if (!is_string($contentToken)) {
            throw new \Exception('Параметр contentToken должен быть строкой');
        }

        if (!$contentToken) {
            throw new \Exception('Не передан contentToken');
        }

        $regionQuery = new Query\Region\GetItemById($regionId);
        $curl->prepare($regionQuery);

        // запрос пользователя
        $userItemQuery = (new \EnterMobile\Repository\User())->getQueryByHttpRequest($request);
        if ($userItemQuery) {
            $curl->prepare($userItemQuery);
        }

        $curl->execute();

        $region = (new \EnterRepository\Region())->getObjectByQuery($regionQuery);
        
        $cart = (new \EnterRepository\Cart())->getObjectByHttpSession($this->getSession(), $config->cart->sessionKey);
        $cartItemQuery = (new \EnterMobile\Repository\Cart())->getPreparedCartItemQuery($cart, $region->id);
        $cartProductListQuery = (new \EnterMobile\Repository\Cart())->getPreparedCartProductListQuery($cart, $region->id);

        $categoryListQuery = new Query\Product\Category\GetTreeList($region->id, 3);
        $curl->prepare($categoryListQuery);

        $mainMenuQuery = new Query\MainMenu\GetItem();
        $curl->prepare($mainMenuQuery);

        $contentItemQuery = new Query\Content\GetItemByToken($contentToken, ['site-mobile']);
        $curl->prepare($contentItemQuery);
        
        $curl->execute();
        
        (new \EnterRepository\Cart())->updateObjectByQuery($cart, $cartItemQuery, $cartProductListQuery);

        $contentPage = new \EnterModel\Content\Page($contentItemQuery->getResult());

        if (!$contentPage->contentHtml || !$contentPage->isAvailableByDirectLink)
            return (new \EnterMobile\Controller\Error\NotFound())->execute($request);

        $contentPage->contentHtml = preg_replace('/http:\/\/www.enter.ru/i', '', $contentPage->contentHtml);
        $contentPage->contentHtml = '<script src="//yandex.st/jquery/1.8.3/jquery.js" type="text/javascript"></script>' . "\n" . $contentPage->contentHtml;

        $pageRequest = new \EnterMobile\Repository\Page\Content\Request();
        $pageRequest->title = $contentPage->title;
        $pageRequest->content = $contentPage->contentHtml;
        $pageRequest->region = $region;
        $pageRequest->mainMenu = (new \EnterRepository\MainMenu())->getObjectByQuery($mainMenuQuery, $categoryListQuery);
        $pageRequest->user = (new \EnterMobile\Repository\User())->getObjectByQuery($userItemQuery);
        $pageRequest->cart = $cart;

        $page = new Page();
        (new \EnterMobile\Repository\Page\Content())->buildObjectByRequest($page, $pageRequest);

        if ($config->debugLevel) $this->getDebugContainer()->page = $page;
        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $renderer = $this->getRenderer();
        $renderer->setPartials([
            'content' => 'page/content/content',
        ]);

        return new Http\Response($renderer->render('layout/default', $page));
    }
}