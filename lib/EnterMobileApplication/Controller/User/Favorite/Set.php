<?php

namespace EnterMobileApplication\Controller\User\Favorite;

use Enter\Http;
use EnterMobileApplication\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\DebugContainerTrait;
use EnterQuery as Query;
use EnterMobileApplication\Controller;
use EnterMobileApplication\Repository;

class Set {
    use ConfigTrait, LoggerTrait, CurlTrait, SessionTrait, DebugContainerTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();

        $token = is_scalar($request->query['token']) ? (string)$request->query['token'] : null;
        if (!$token) {
            throw new \Exception('Не указан token', Http\Response::STATUS_BAD_REQUEST);
        }
        $productId = is_scalar($request->query['productId']) ? (string)$request->query['productId'] : null;
        if (!$productId) {
            throw new \Exception('Не указан productId', Http\Response::STATUS_BAD_REQUEST);
        }

        try {
            $userItemQuery = new Query\User\GetItemByToken($token);
            $curl->prepare($userItemQuery);

            $productItemQuery = new Query\Product\GetItemById($productId, $config->region->defaultId, ['model' => false, 'related' => false]);
            $productDescriptionListQuery = new Query\Product\GetDescriptionListByIdList([$productId]);
            $curl->prepare($productItemQuery);
            $curl->prepare($productDescriptionListQuery);

            $curl->execute();

            $user = (new \EnterRepository\User())->getObjectByQuery($userItemQuery);
            /*
            if ($user) {
                $response->token = $token;
            }
            */

            $product = (new \EnterRepository\Product())->getObjectByQuery($productItemQuery, [$productDescriptionListQuery]);
            if (!$product) {
                return new Controller\Error\NotFound($request, 'Товар не найден');
            }

            $favoriteItemQuery = new Query\User\Favorite\SetItemByUserUi($user->ui, $product);
            $favoriteItemQuery->setTimeout(3 * $config->crmService->timeout);
            $curl->query($favoriteItemQuery);

            $favoriteItemQuery->getResult();
        } catch (\Exception $e) {
            if ($config->debugLevel) $this->getDebugContainer()->error = $e;
        }

        return (new Controller\User\Favorite\Get())->execute($request);
    }
}
