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
        CurlTrait;

    /**
     * @param Http\Request $request
     * @return Http\JsonResponse
     * @throws \Exception
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();
        $session = $this->getSession();

        $error = null;
        try {
            $productUi = $request->query['productUi'];

            $userItemQuery = (new \EnterMobile\Repository\User())->getQueryBySessionAndHttpRequest($session, $request);
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
            $error = $e;
        }

        if ($request->isXmlHttpRequest()) {
            $response = new Http\JsonResponse([
                'success' => (bool)$error,
            ]);
        } else {
            $response = (new \EnterAggregator\Controller\Redirect())->execute(isset($request->server['HTTP_REFERER']) ? $request->server['HTTP_REFERER'] : '/', 302);
        }

        return $response;
    }
}