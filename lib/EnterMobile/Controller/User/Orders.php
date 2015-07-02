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

class Orders {

    use ConfigTrait, CurlTrait, MustacheRendererTrait, DebugContainerTrait;

    public function execute(Http\Request $request) {
        $curl = $this->getCurl();
        $user = new \EnterMobile\Repository\User();

        $token = $user->getTokenByHttpRequest($request);

        $ordersQuery = new Query\Order\GetListByUserToken($token, 0, 40);
        $curl->prepare($ordersQuery);
        $curl->execute();

        $page = new Page();
        $page->title = 'Заголовок';
        $page->content = $ordersQuery->getResult();

        // рендер
        $renderer = $this->getRenderer();
        $renderer->setPartials([
            'content' => 'page/private/orders'
        ]);

        $content = $renderer->render('layout/default', $page);

        return new Http\Response($content);
    }
}