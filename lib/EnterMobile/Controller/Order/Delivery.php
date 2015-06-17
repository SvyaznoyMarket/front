<?php

namespace EnterMobile\Controller\Order;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\DebugContainerTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterMobile\Model;
use EnterMobile\Controller;
use EnterMobile\Repository;
use EnterMobile\Routing;
use EnterMobile\Model\Page\Order\Delivery as Page;

class Delivery {
    use ConfigTrait, CurlTrait, SessionTrait, LoggerTrait, RouterTrait, MustacheRendererTrait, DebugContainerTrait;

    /**
     * @param Http\Request $request
     * @return Http\Response
     * @throws \Exception
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $session = $this->getSession();
        $router = $this->getRouter();
        $cartRepository = new \EnterRepository\Cart();

        // ид региона
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        // токен пользователя
        $userToken = (new \EnterMobile\Repository\User())->getTokenByHttpRequest($request);

        // корзина
        $cart = $cartRepository->getObjectByHttpSession($session);
        // проверяет наличие товаров в корзине
        if (!$cart->product) {
            $this->getLogger()->push(['type' => 'error', 'message' => 'Пустая корзина', 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['order.split', 'critical']]);

            $url = $router->getUrlByRoute(new Routing\Cart\Index());

            return
                $request->isXmlHttpRequest()
                    ? new Http\JsonResponse(
                        [
                            'redirect' => $url,
                        ],
                        Http\Response::STATUS_FOUND
                    )
                : (new \EnterAggregator\Controller\Redirect())->execute($url, 302)
            ;
        }

        // изменения
        $changeData = $request->data['change'] ?: null;
        // если это первый запрос на разбиение, то подставляет данные пользователя
        $userFromSplit = null;
        if (!$changeData) {
            $userForm = new Model\Form\Order\UserForm((array)$session->get($config->order->userSessionKey));
            if (!$userForm->isValid()) {
                $this->getLogger()->push(['type' => 'error', 'message' => 'Нет данных о пользователе', 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['order.split', 'critical']]);

                $url = $router->getUrlByRoute(new Routing\Order\Index());

                return
                    $request->isXmlHttpRequest()
                    ? new Http\JsonResponse([
                        'redirect' => $url,
                    ])
                    : (new \EnterAggregator\Controller\Redirect())->execute($url, 302)
                ;
            }

            $userFromSplit = new \EnterModel\Cart\Split\User();
            $userFromSplit->email = $userForm->email;
            $userFromSplit->phone = $userForm->phone;
            $userFromSplit->firstName = $userForm->firstName;
            $userFromSplit->bonusCardNumber = $userForm->mnogoruNumber;
        }

        // предыдущее разбиение
        $previousSplitData = null;
        if ($changeData) {
            $previousSplitData = $session->get($config->order->splitSessionKey);
        }

        // контроллер
        $controller = new \EnterAggregator\Controller\Cart\Split();
        // запрос для контроллера
        $controllerRequest = $controller->createRequest();
        $controllerRequest->regionId = $regionId;
        $controllerRequest->userToken = $userToken;
        $controllerRequest->shopId = null;
        $controllerRequest->changeData = $changeData;
        $controllerRequest->previousSplitData = $previousSplitData;
        $controllerRequest->cart = $cart;
        $controllerRequest->enrichCart = true;
        $controllerRequest->userFromSplit = $userFromSplit;
        // при получении данных о разбиении корзины - записать их в сессию немедленно
        $controllerRequest->splitReceivedSuccessfullyCallback->handler = function() use (&$controllerRequest, &$config, &$session) {
            $session->set($config->order->splitSessionKey, $controllerRequest->splitReceivedSuccessfullyCallback->splitData);
        };
        // ответ от контроллера
        $controllerResponse = $controller->execute($controllerRequest);

        // запрос для получения страницы
        $pageRequest = new Repository\Page\Order\Delivery\Request();
        $pageRequest->httpRequest = $request;
        $pageRequest->region = $controllerResponse->region;
        $pageRequest->user = $controllerResponse->user;
        $pageRequest->cart = $cart;
        $pageRequest->split = $controllerResponse->split;
        //$pageRequest->formErrors = $controllerResponse->errors; // TODO
        //die(json_encode($pageRequest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // страница
        $page = new Page();
        (new Repository\Page\Order\Delivery())->buildObjectByRequest($page, $pageRequest);

        // debug
        if ($config->debugLevel) $this->getDebugContainer()->page = $page;
        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        // рендер
        $renderer = $this->getRenderer();

        if ($request->isXmlHttpRequest()) {
            $content = $renderer->render('page/order/delivery/form', $page->content);
        } else {
            $renderer->setPartials([
                'content' => 'page/order/delivery/content',
            ]);
            $content = $renderer->render('layout/simple', $page);
        }

        return new Http\Response($content);
    }
}