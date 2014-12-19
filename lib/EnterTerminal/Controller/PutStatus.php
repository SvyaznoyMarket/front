<?php

namespace EnterTerminal\Controller {

    use Enter\Http;
    use EnterAggregator\CurlTrait;
    use EnterQuery as Query;

    class PutStatus {
        use CurlTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $curl = $this->getCurl();

            $ui = $request->query['ui'];
            if (!$ui) {
                throw new \Exception('Не передан параметр ui', Http\Response::STATUS_BAD_REQUEST);
            }

            $statusQuery = new Query\Terminal\SetStatusByUi($ui, (array)$request->data->all());
            $curl->prepare($statusQuery);

            $curl->execute();

            $result = (array)$statusQuery->getResult();
            if (!(bool)$result) {
                throw new \Exception('Неверный ответ');
            }

            return new Http\JsonResponse($result);
        }
    }
}
