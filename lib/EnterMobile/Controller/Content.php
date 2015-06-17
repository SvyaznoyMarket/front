<?php
namespace EnterMobile\Controller;

use Enter\Http;
use EnterAggregator\CurlTrait;
use EnterAggregator\DebugContainerTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterMobile\ConfigTrait;
use EnterQuery as Query;
use EnterMobile\Model\Page\Content as Page;

class Content {
    use ConfigTrait, CurlTrait, MustacheRendererTrait, DebugContainerTrait;

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

        list($cart, $cartItemQuery, $cartProductListQuery) = (new \EnterMobile\Repository\Cart())->getObjectAndPreparedQueries($regionId);

        $curl->execute();

        $region = (new \EnterRepository\Region())->getObjectByQuery($regionQuery);

        (new \EnterRepository\Cart())->updateObjectByQuery($cart, $cartItemQuery, $cartProductListQuery);

        $categoryListQuery = new Query\Product\Category\GetTreeList($region->id, 3);
        $curl->prepare($categoryListQuery);

        $mainMenuQuery = new Query\MainMenu\GetItem();
        $curl->prepare($mainMenuQuery);

        $contentItemQuery = new Query\Content\GetItemByToken($contentToken, false);
        $curl->prepare($contentItemQuery);

        $curl->execute();

        // wordpress (content.enter.ru) при отсутствии запрашиваемой страницы вместо 404 отдаёт 301 редирект на запрашиваемую страницу со слешем в конце
        if ($contentItemQuery->getError() && in_array($contentItemQuery->getError()->getCode(), [404, 301]))
            return (new \EnterMobile\Controller\Error\NotFound())->execute($request);

        $contentItem = $contentItemQuery->getResult();
        $contentItem['content'] = preg_replace('/http:\/\/www.enter.ru/i', '', $contentItem['content']);

        $pageRequest = new \EnterMobile\Repository\Page\Content\Request();
        $pageRequest->title = $contentItem['title'];
        $pageRequest->content = $contentItem['content'];
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