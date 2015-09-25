<?php

namespace EnterMobileApplication\Controller\Product\Review {

    use Enter\Http;
    use EnterAggregator\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterMobileApplication\Controller;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterMobileApplication\Controller\Product\Review\Create\Response;

    class Create {
        use ConfigTrait, CurlTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();

            $productId = trim((string)$request->query['productId']);
            if (!$productId) {
                throw new \Exception('Не указан параметр productId', Http\Response::STATUS_BAD_REQUEST);
            }

            $reviewData = $request->data['review'];
            if (!is_array($reviewData)) {
                throw new \Exception('Не указан параметр review', Http\Response::STATUS_BAD_REQUEST);
            }

            $review = new Model\Product\Review();
            $review->createdAt = new \DateTime('now');
            foreach ($reviewData as $k => $v) {
                if (!property_exists($review, $k)) continue;

                $review->{$k} = $v;
            }

            // ответ
            $response = new Response();

            $productListQuery = new Query\Product\GetListByIdList([$productId], $config->region->defaultId, ['model' => false, 'related' => false]);
            $productDescriptionListQuery = new Query\Product\GetDescriptionListByIdList([$productId]);
            $curl->prepare($productListQuery);
            $curl->prepare($productDescriptionListQuery);

            $curl->execute();

            $product = (new \EnterRepository\Product())->getObjectByQueryList([$productListQuery], [$productDescriptionListQuery]);
            if (!$product) {
                return (new Controller\Error\NotFound())->execute($request, 'Товар не найден');
            }

            // подготовка отзывов
            $createQuery = new Query\Product\Review\CreateItemByProductUi(
                $product->ui,
                $review
            );
            $curl->query($createQuery);

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterMobileApplication\Controller\Product\Review\Create {
    use EnterModel as Model;

    class Response {}
}
