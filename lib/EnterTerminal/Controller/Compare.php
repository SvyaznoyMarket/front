<?php

namespace EnterTerminal\Controller;

use Enter\Http;
use EnterTerminal\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\SessionTrait;
use EnterCurlQuery as Query;
use EnterModel as Model;
use EnterTerminal\Model\Page\Compare as Page;

class Compare {
    use ConfigTrait, LoggerTrait, CurlTrait, SessionTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\JsonResponse
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $session = $this->getSession();
        $curl = $this->getCurl();
        $compareRepository = new \EnterRepository\Compare();

        // ид магазина
        $shopId = (new \EnterTerminal\Repository\Shop())->getIdByHttpRequest($request); // FIXME

        // запрос магазина
        $shopItemQuery = new Query\Shop\GetItemById($shopId);
        $curl->prepare($shopItemQuery);

        $curl->execute();

        // магазин
        $shop = (new \EnterRepository\Shop())->getObjectByQuery($shopItemQuery);
        if (!$shop) {
            throw new \Exception(sprintf('Магазин #%s не найден', $shopId));
        }

        // сравнение из сессии
        $compare = $compareRepository->getObjectByHttpSession($session);

        $productsById = [];
        foreach ($compare->product as $compareProduct) {
            $productsById[$compareProduct->id] = null;
        }

        $productListQuery = null;
        if ((bool)$productsById) {
            $productListQuery = new Query\Product\GetListByIdList(array_keys($productsById), $shop->regionId);
            $curl->prepare($productListQuery);
        }

        $curl->execute();

        if ($productListQuery) {
            $productsById = (new \EnterRepository\Product())->getIndexedObjectListByQueryList([$productListQuery], function(&$item) {
                // оптимизация
                $item['media'] = [reset($item['media'])];
            });
        }

        // сравнение свойств товара
        $compareRepository->compareProductObjectList($compare, $productsById);

        // страница
        $page = new Page();
        $page->groups = $compareRepository->getGroupListByObject($compare, $productsById);
        foreach ($compare->product as $compareProduct) {
            $product = !empty($productsById[$compareProduct->id])
                ? $productsById[$compareProduct->id]
                : new Model\Product([
                    'id' => $compareProduct->id,
                ]);

            $page->products[] = $product;
        }

        // response
        return new Http\JsonResponse($page);
    }
}