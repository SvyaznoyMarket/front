<?php

namespace EnterMobile\Controller\User;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterAggregator\DebugContainerTrait;
use EnterAggregator\CurlTrait;
use EnterMobile\Repository;
use EnterQuery as Query;
use EnterMobile\Model\Page\DefaultPage as Page;

class Order {

    use ConfigTrait, CurlTrait, MustacheRendererTrait, DebugContainerTrait;

    public function execute(Http\Request $request) {
        $curl = $this->getCurl();

        $orderId = $request->query['orderId'];

        $user = new \EnterMobile\Repository\User();
        $token = $user->getTokenByHttpRequest($request);

        $orderQuery = new Query\Order\GetItemById('site', $token, $orderId);
        $curl->prepare($orderQuery);
        $curl->execute();

        $productIds = [];
        $productMap = [];
        $orderResult = $orderQuery->getResult();
        foreach ($orderResult['product'] as $key => $product) {
            $productIds[] = $product['id'];
            $productMap[$product['id']] = $key;
        }

        $productsInfo = new Query\Product\GetDescriptionListByIdList($productIds, ['media' => 1]);
        $curl->prepare($productsInfo);
        $curl->execute();

        $productsInfoResult = $productsInfo->getResult();

        foreach ($productsInfoResult as $key => $productInfo) {
            $coreId = $productInfo['core_id'];

            $orderResult['product'][$productMap[$coreId]]['image'] = $productInfo['medias'][0]['sources'][0]['url'];
            $orderResult['product'][$productMap[$coreId]]['name'] = $productInfo['name'];
        }

        $page = new Page();
        $page->title = 'Заголовок';
        $page->content = $orderResult;

        // рендер
        $renderer = $this->getRenderer();
        $renderer->setPartials([
            'content' => 'page/private/order'
        ]);

        $content = $renderer->render('layout/default', $page);

        return new Http\Response($content);
    }
}