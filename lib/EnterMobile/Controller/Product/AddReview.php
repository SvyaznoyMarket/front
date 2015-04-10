<?php

namespace EnterMobile\Controller\Product;

use Enter\Http;
use EnterAggregator\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterQuery as Query;
use EnterMobile\Repository;
use EnterModel as Model;
use EnterMobile\Model\Page\Product\ReviewList as Page;

class AddReview {
    use ConfigTrait, CurlTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $curl = $this->getCurl();

        $reviewData = $request->data['review'];
        $productId = $request->data['review']['productId'];


        $review = new Model\Product\Review();
        $review->createdAt = new \DateTime('now');
        foreach ($reviewData as $key => $value) {
            if (!property_exists($review, $key)) continue;

            $review->{$key} = $value;
        }

        $productItemQuery = new Query\Product\GetItemById($productId, $config->region->defaultId);
        $curl->prepare($productItemQuery);

        $curl->execute();

        $product = (new \EnterRepository\Product())->getObjectByQuery($productItemQuery);

        if (!$product) {
            return (new \EnterMobile\Controller\Error\NotFound())->execute($request, 'Товар не найден');
        }

        $responseData = [];
        try {
            $createQuery = new Query\Product\Review\CreateItemByProductUi(
                $product->ui,
                $review
            );

            $curl->prepare($createQuery);
            $curl->execute();

            $createQuery->getResult();

            $responseData['success'] = true;
        } catch(\Exception $e) {

            $responseData = [
                'success' => false,
                'error'   => ['code' => $e->getCode(), 'message' => $e->getMessage()],
            ];
        }

        return new Http\JsonResponse($responseData);
    }
}