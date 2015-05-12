<?php

namespace EnterTerminal\Controller {

    use Enter\Http;
    use EnterTerminal\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterTerminal\Controller;
    use EnterTerminal\Repository;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterTerminal\Controller\MainPromo\Response;

    class MainPromo {
        use ConfigTrait, CurlTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();

            $promoRepository = new \EnterRepository\Promo();

            // ид региона
            $regionId = (new \EnterTerminal\Repository\Region())->getIdByHttpRequest($request);
            if (!$regionId) {
                throw new \Exception('Не передан параметр regionId', Http\Response::STATUS_BAD_REQUEST);
            }

            // запрос региона
            $regionItemQuery = new Query\Region\GetItemById($regionId);
            $curl->prepare($regionItemQuery);

            $curl->execute();

            // регион
            $region = (new Repository\Region())->getObjectByQuery($regionItemQuery);
            if (!$region) {
                return (new Controller\Error\NotFound())->execute($request, sprintf('Регион #%s не найден', $regionId));
            }

            // запрос баннеров
            $promoListQuery = new Query\Promo\GetList($config->applicationTags);
            $curl->prepare($promoListQuery);

            $curl->execute();

            // баннеры
            $promos = $promoRepository->getObjectListByQuery($promoListQuery);

            // ответ
            $response = new Response();
            $response->region = $region;
            $response->promos = $promos;

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterTerminal\Controller\MainPromo {
    use EnterModel as Model;

    class Response {
        /** @var Model\Region */
        public $region;
        /** @var \EnterModel\Promo[] */
        public $promos = [];
    }
}