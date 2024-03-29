<?php

namespace EnterMobile\Controller;

use Enter\Http;
use EnterAggregator\SessionTrait;
use EnterMobile\ConfigTrait;
use EnterAggregator\AbTestTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterAggregator\DebugContainerTrait;
use EnterMobile\Controller;
use EnterMobile\Repository;
use EnterQuery as Query;
use EnterMobile\Model;
use EnterMobile\Model\Page\ProductCard as Page;

class ProductCard {
    use ConfigTrait,
        MustacheRendererTrait,
        DebugContainerTrait,
        SessionTrait,
        AbTestTrait
    ;

    /**
     * @param Http\Request $request
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $session = $this->getSession();
        $productRepository = new \EnterRepository\Product();

        // ид региона
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        // токен товара
        $productToken = $productRepository->getTokenByHttpRequest($request);

        $cart = (new \EnterRepository\Cart())->getObjectByHttpSession($this->getSession(), $config->cart->sessionKey);

        // контроллер
        $controller = new \EnterAggregator\Controller\ProductCard();
        // запрос для контроллера
        $controllerRequest = $controller->createRequest();
        $controllerRequest->config->mainMenu = true;
        $controllerRequest->config->review = true;
        $controllerRequest->config->favourite = true;
        $controllerRequest->regionId = $regionId;
        $controllerRequest->productCriteria = ['token' => $productToken];
        $controllerRequest->userToken = (new \EnterMobile\Repository\User())->getTokenBySessionAndHttpRequest($session, $request);
        $controllerRequest->cart = $cart;

        // ответ от контроллера
        $controllerResponse = $controller->execute($controllerRequest);

        if (!$controllerResponse->product) {
            return (new Controller\Error\NotFound())->execute($request, sprintf('Товар @%s не найден', $productToken));
        }
        if ($controllerResponse->product->link !== $request->getPathInfo()) {
            return (new \EnterAggregator\Controller\Redirect())->execute($controllerResponse->product->link . ((bool)$request->getQueryString() ? ('?' . $request->getQueryString()) : ''), Http\Response::STATUS_MOVED_PERMANENTLY);
        }

        // запрос для получения страницы
        $pageRequest = new Repository\Page\ProductCard\Request();
        $pageRequest->httpRequest = $request;
        $pageRequest->region = $controllerResponse->region;
        $pageRequest->mainMenu = $controllerResponse->mainMenu;
        $pageRequest->user = $controllerResponse->user;
        $pageRequest->cart = $cart;
        $pageRequest->product = $controllerResponse->product;
        $pageRequest->accessoryCategories = $controllerResponse->accessoryCategories;
        $pageRequest->hasCredit = $controllerResponse->hasCredit;
        //die(json_encode($pageRequest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // страница
        $page = new Page();
        (new Repository\Page\ProductCard())->buildObjectByRequest($page, $pageRequest);

        // debug
        if ($config->debugLevel) $this->getDebugContainer()->page = $page;
        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        // рендер
        $renderer = $this->getRenderer();

        if ('disabled' === $this->getAbTest()->getObjectByToken('productcard')->chosenItem->token) {
            $renderer->setPartials([
                'content' => $pageRequest->product->getSlotPartnerOffer() ? 'page/product-card-slot/content' : 'page/product-card/content',
            ]);

        } else {
            $renderer->setPartials([
                'content' => 'page/product-card-new/content'
            ]);
        }

        $content = $renderer->render('layout/default', $page);

        // http-ответ
        $response = new Http\Response($content);

        return $response;
    }
}