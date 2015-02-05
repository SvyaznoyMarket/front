<?php

namespace EnterTerminal\Controller {

    use Enter\Http;
    use EnterTerminal\ConfigTrait;
    use EnterAggregator\CurlTrait;
    use EnterTerminal\Controller;
    use EnterQuery as Query;
    use EnterModel as Model;
    use EnterTerminal\Controller\SpecialPage\Response;

    class SpecialPage {
        use ConfigTrait, CurlTrait;

        /**
         * @param Http\Request $request
         * @throws \Exception
         * @return Http\JsonResponse
         */
        public function execute(Http\Request $request) {
            $config = $this->getConfig();
            $curl = $this->getCurl();

            $tokens = is_array($request->query['tokens']) ? $request->query['tokens'] : null;
            if (!$tokens) {
                throw new \Exception('Не указан параметр tokens', Http\Response::STATUS_BAD_REQUEST);
            }

            // запрос магазина
            $listQuery = new Query\SpecialPage\GetListByTokenList($tokens);
            $curl->prepare($listQuery);

            $curl->execute();

            // специальные страницы
            $pages = [];
            foreach ($listQuery->getResult()['special_pages'] as $item) {
                $pages[] = $item;
            }

            // ответ
            $response = new Response();
            $response->pages = $pages;

            return new Http\JsonResponse($response);
        }
    }
}

namespace EnterTerminal\Controller\SpecialPage {
    use EnterModel as Model;

    class Response {
        /** @var array[] */
        public $pages = [];
    }
}