<?php

namespace EnterMobile\Controller\Product;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\RouterTrait;
use EnterMobile\Routing;
use EnterMobile\Controller;
use EnterQuery as Query;
use EnterMobile\Model;
//use EnterMobile\Model\JsonPage as Page;
use EnterMobile\Repository;

class AddToFavorite {
    use ConfigTrait,
        RouterTrait,
        LoggerTrait,
        CurlTrait,
        SessionTrait;

    /**
     * @param Http\Request $request
     * @return Http\JsonResponse
     * @throws \Exception
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $session = $this->getSession();
        $curl = $this->getCurl();
        $router = $this->getRouter();

        $response = [];

        try {
            $postData = $request->data->all();
            $productUi = $postData['productUi'];

            if (!$productUi) {
                throw new \Exception(
                    'Не указан product_ui',
                    402
                );
            }
            // проверка пользователя
            $userItemQuery = (new \EnterMobile\Repository\User())->getQueryByHttpRequest($request);
            if (!$userItemQuery) {
                throw new \Exception(
                    'Пользователь не авторизован',
                    401
                );
            }
            $curl->prepare($userItemQuery);
            $curl->execute();

            $userUi = (new \EnterMobile\Repository\User())->getObjectByQuery($userItemQuery)->ui;
            $productModel = new \EnterModel\Product([
                'ui' => $productUi
            ]);

            $addToFavQuery = new Query\User\Favorite\SetItemByUserUi($userUi, $productModel);
            $curl->prepare($addToFavQuery);
            $curl->execute();

            $response = [
                'data' => [
                    'success' => true
                ],
                'statusCode' => 200
            ];

        } catch(\Exception $e) {

            switch($e->getCode()) {
                case 401:
                    $redirectUrl = (new \EnterMobile\Repository\User())->getRedirectUrlByHttpRequest($request, $router->getUrlByRoute(new Routing\User\Auth()));
                    return (new \EnterAggregator\Controller\Redirect())->execute($redirectUrl, 302);
                    break;
                case 402:
                    $response = [
                        'data' => [
                            'errorCode' => 402,
                            'errorInfo' => 'Не указан ui товара'
                        ],
                        'statusCode' => 200
                    ];
                    break;
                case 403:
                    $response = [
                        'data' => [
                            'errorInfo' => $e->getMessage()
                        ],
                        'statusCode' => 200
                    ];
            }

        }


        return new Http\JsonResponse($response);
    }
}