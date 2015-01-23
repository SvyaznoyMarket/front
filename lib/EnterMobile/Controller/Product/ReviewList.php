<?php

namespace EnterMobile\Controller\Product;

use Enter\Http;
use EnterAggregator\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterQuery as Query;
use EnterMobile\Repository;
use EnterMobile\Model\Page\Product\ReviewList as Page;

class ReviewList {
    use ConfigTrait, CurlTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();
        $reviewRepository = new \EnterRepository\Product\Review();

        $productId = trim((string)$request->query['productId']);
        if (!$productId) {
            throw new \Exception('Не указан параметр productId', Http\Response::STATUS_BAD_REQUEST);
        }

        $pageNum = (int)$request->query['page'];
        if (!$pageNum) {
            throw new \Exception('Не указан параметр page', Http\Response::STATUS_BAD_REQUEST);
        }

        $limit = $config->productReview->itemsInCard;

        // подготовка отзывов
        $reviewListQuery = new Query\Product\Review\GetListByProductId(
            $productId,
            $pageNum,
            $limit
        );
        $curl->prepare($reviewListQuery);

        $curl->execute();

        $reviews = $reviewRepository->getObjectListByQuery($reviewListQuery);
        $reviewCount = $reviewRepository->countObjectListByQuery($reviewListQuery);

        // запрос для получения страницы
        $pageRequest = new Repository\Page\Product\ReviewList\Request();
        $pageRequest->reviews = $reviews;
        $pageRequest->reviewCount = $reviewCount;
        $pageRequest->pageNum = $pageNum + 1;
        $pageRequest->limit = $limit;

        // страница
        $page = new Page();
        (new Repository\Page\Product\ReviewList())->buildObjectByRequest($page, $pageRequest);
        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        // http-ответ
        $response = new Http\JsonResponse([
            'result' => $page,
        ]);

        return $response;
    }
}