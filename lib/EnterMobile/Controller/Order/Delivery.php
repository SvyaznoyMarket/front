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
    use ControllerTrait {
        ConfigTrait::getConfig insteadof ControllerTrait;
    }

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
        $cartSessionKey = $this->getCartSessionKeyByHttpRequest($request);

        // ид региона
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        // ид магазина
        $shopId = is_scalar($request->query['shopId']) ? (string)$request->query['shopId']: null;

        // токен пользователя
        $userToken = (new Repository\User())->getTokenByHttpRequest($request);

        // корзина
        $cart = $cartRepository->getObjectByHttpSession($session, $cartSessionKey);
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
        $changeData = is_array($request->data['change']) ? $request->data['change'] : [];
        // если это первый запрос на разбиение, то подставляет данные пользователя
        $userFromSplit = null;
        if (!$request->isXmlHttpRequest()) {
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
        $previousSplitData = $session->get($config->order->splitSessionKey);

        if ($previousSplitData && !$changeData && $userFromSplit) {
            $changeData['user'] = $userFromSplit->toArray();
        }

        // контроллер
        $controller = new \EnterAggregator\Controller\Cart\Split();
        // запрос для контроллера
        $controllerRequest = $controller->createRequest();
        $controllerRequest->regionId = $regionId;
        $controllerRequest->userToken = $userToken;
        $controllerRequest->shopId = $shopId;
        $controllerRequest->changeData = (new \EnterRepository\Cart())->dumpSplitChange($changeData, $previousSplitData);
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

        // если в разбиении нет заказа или произошла ошибка из-за предыдущего разбиения, то удаляем предыдущее разбиение
        if (!$controllerResponse->split->orders) {
            $session->remove($config->order->splitSessionKey);
        }
        foreach ($controllerResponse->errors as $error) {
            if (!isset($error['code'])) continue;

            if (in_array($error['code'], [600])) {
                $session->remove($config->order->splitSessionKey);
                break;
            }
        }

        // запрос для получения страницы
        $pageRequest = new Repository\Page\Order\Delivery\Request();
        $pageRequest->httpRequest = $request;
        $pageRequest->region = $controllerResponse->region;
        $pageRequest->user = $controllerResponse->user;
        $pageRequest->cart = $cart;
        $pageRequest->split = $controllerResponse->split;
        $pageRequest->shopId = $shopId;
        $pageRequest->formErrors = $session->flashBag->get('orderForm.error') ?: $controllerResponse->errors;
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