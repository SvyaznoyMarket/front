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

        $productListQuery = new Query\Product\GetListByIdList([$productId], $config->region->defaultId, ['related' => false]);
        $productDescriptionListQuery = new Query\Product\GetDescriptionListByIdList([$productId]);
        $curl->prepare($productListQuery);
        $curl->prepare($productDescriptionListQuery);

        $curl->execute();

        $product = (new \EnterRepository\Product())->getObjectByQueryList([$productListQuery], [$productDescriptionListQuery]);

        if (!$product) {
            return (new \EnterMobile\Controller\Error\NotFound())->execute($request, 'Товар не найден');
        }

        $responseData = [];
        try {
            $createQuery = new Query\Product\Review\CreateItemByProductUi(
                $product->ui,
                $review
            );

            $curl->query($createQuery);

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