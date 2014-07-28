<?php

namespace EnterTerminal\Controller;

use Enter\Http;
use EnterAggregator\CurlTrait;
use EnterCurlQuery as Query;
use EnterTerminal\Model\Page\Config as Page;

class Config {
    use CurlTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $curl = $this->getCurl();

        if (!is_string($request->query['ip'])) {
            throw new \Exception('Параметр ip должен быть строкой');
        }

        $infoQuery = new Query\Terminal\GetInfoByIp($request->query['ip']);
        $curl->prepare($infoQuery);

        $curl->execute();

        $page = new Page();

        $data = $infoQuery->getResult();
        if (!$data) {
            throw new \Exception('Не удалось получить конфигурацию терминала');
        }
        $shopId = $page->info['shop_id'];

        $page->info = $data;

        $shopQuery = new Query\Shop\GetItemById($shopId);
        $curl->prepare($shopQuery);

        $curl->execute();

        $shop = (new \EnterTerminal\Repository\Shop())->getObjectByQuery($shopQuery);
        if (!$shop) {
            throw new \Exception(sprintf('Магазин #%s не найден', $shopId));
        }

        $page->shop = $shop;

        return new Http\JsonResponse($page);
    }
}