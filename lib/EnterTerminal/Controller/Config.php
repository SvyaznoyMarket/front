<?php

namespace EnterTerminal\Controller;

use Enter\Http;
use EnterAggregator\CurlTrait;
use EnterCurlQuery as Query;

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

        $info = $infoQuery->getResult();
        if ($info) {
            $configModel = new \EnterTerminal\Model\Page\Config($info);
        } else {
            throw new \Exception('Не удалось получить конфигурацию терминала');
        }

        $shopQuery = new Query\Shop\GetItemById($configModel->shop->id);
        $curl->prepare($shopQuery);
        $curl->execute();

        $shop = (new \EnterTerminal\Repository\Shop())->getObjectByQuery($shopQuery);
        if (!$shop) {
            throw new \Exception(sprintf('Магазин #%s не найден', $configModel->shop->id));
        }

        $configModel->shop = $shop;
        return new Http\JsonResponse($configModel);
    }
}