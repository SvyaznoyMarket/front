<?php

namespace EnterMobile\Controller\Order;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\DebugContainerTrait;
use EnterModel as Model;
use EnterQuery as Query;
use EnterMobile\Routing;
use EnterMobile\Controller;
use EnterMobile\Repository;

class Create {
    use ConfigTrait, CurlTrait, SessionTrait, LoggerTrait, RouterTrait, DebugContainerTrait;

    /**
     * @param Http\Request $request
     * @return Http\Response
     * @throws \Exception
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();
        $session = $this->getSession();
        $router = $this->getRouter();
        $cartRepository = new \EnterRepository\Cart();

        if (!isset($request->data['accept'])) {
            // TODO
        }

        $splitData = (array)$session->get($config->order->splitSessionKey);
        if (!$splitData) {
            throw new \Exception('Не найдено предыдущее разбиение');
        }

        if (!isset($splitData['cart']['product_list'])) {
            throw new \Exception('Не найдены товары в корзине');
        }

        // ид региона
        $regionId = (new \EnterRepository\Region())->getIdByHttpRequestCookie($request);

        // запрос региона
        $regionQuery = new Query\Region\GetItemById($regionId);
        $curl->prepare($regionQuery);

        // токен пользователя
        $userToken = (new \EnterRepository\User())->getTokenByHttpRequest($request);

        // запрос пользователя
        $userItemQuery = null;
        if ($userToken && (0 !== strpos($userToken, 'anonymous-'))) {
            $userItemQuery = new Query\User\GetItemByToken($userToken);
            $curl->prepare($userItemQuery);
        }

        $curl->execute();

        $region = (new \EnterRepository\Region())->getObjectByQuery($regionQuery);

        // пользователь
        $user = null;
        try {
            if ($userItemQuery) {
                $user = (new \EnterRepository\User())->getObjectByQuery($userItemQuery);
            }
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['controller']]);
        }

        // корзина из данных о разбиении
        $cart = new Model\Cart();
        foreach ($splitData['cart']['product_list'] as $productItem) {
            $cartProduct = new Model\Cart\Product($productItem);
            $cartRepository->setProductForObject($cart, $cartProduct);
        }

        $split = null;
        try {
            $split = new Model\Cart\Split($splitData);

            // дополнительные свойства разбиения
            $split->region = $region;
            $split->clientIp = $request->getClientIp();

            // пользователь
            if ($user) {
                $split->user->id = $user->id;
                $split->user->ui = $user->ui;
            }

            // meta
            $metas = [];

            $controller = new \EnterAggregator\Controller\Order\Create();
            $controllerResponse = new \EnterAggregator\Controller\Order\Create\Response;
            $controllerResponse = unserialize(file_get_contents('/home/green/desktop/order-create-response.txt'));

            /*
            $controllerResponse = $controller->execute(
                $region->id,
                $split,
                $metas
            );
            */
            die(var_dump($controllerResponse));

            //file_put_contents('/home/green/desktop/order-create-response.txt', serialize($controllerResponse)); exit();
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'tag' => ['critical', 'order']]);

            throw new \Exception($e->getMessage());
        }

        // http-ответ
        $response = null;

        return $response;
    }
}