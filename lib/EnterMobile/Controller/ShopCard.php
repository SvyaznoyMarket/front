<?php

namespace EnterMobile\Controller;

use Enter\Http;
use EnterAggregator\SessionTrait;
use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterAggregator\DebugContainerTrait;
use EnterMobile\Repository;
use EnterQuery as Query;
use EnterMobile\Model;
use EnterMobile\Model\Page\DefaultPage as Page;

class ShopCard {

    use ConfigTrait,
        LoggerTrait,
        CurlTrait,
        MustacheRendererTrait,
        DebugContainerTrait,
        SessionTrait;

    public function execute(Http\Request $request) {
        $renderer = $this->getRenderer();

        $page = new Page();
        $renderer->setPartials([
            'content' => 'page/shops/card',
        ]);
        $content = $renderer->render('layout/shops', $page);


        return new Http\Response($content);
    }
}