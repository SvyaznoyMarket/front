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

class DeleteFavorite {
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

        try {
            $postData = $request->data->all();
            $productUi = $postData['productUi'];

            $userItemQuery = (new \EnterMobile\Repository\User())->getQueryByHttpRequest($request);
            if ($userItemQuery) {
                $curl->prepare($userItemQuery);
            }
            $curl->execute();
            $userUi = (new \EnterMobile\Repository\User())->getObjectByQuery($userItemQuery)->ui;


            $productModel = new \EnterModel\Product([
                'ui' => $productUi
            ]);


            $removeFavQuery = new Query\User\Favorite\DeleteItemByUserUi($userUi, $productModel);
            $curl->prepare($removeFavQuery);

            $curl->execute();
        } catch (\Exception $e) {
            echo '<pre>';
            print_r ($e);
            echo '</pre>';
        }



        return new Http\JsonResponse([
            'data' => [
                'success' => true
            ],
            'statusCode' => 200
        ]);
    }
}