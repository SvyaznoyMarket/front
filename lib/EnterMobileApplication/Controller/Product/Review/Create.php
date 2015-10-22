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

            $productItemQuery = new Query\Product\GetItemById($productId, $config->region->defaultId, ['related' => false]);
            $curl->prepare($productItemQuery);

            $curl->execute();

            $product = (new \EnterRepository\Product())->getObjectByQuery($productItemQuery);
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
