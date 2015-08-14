<?php

namespace EnterMobile\Controller\Shop;

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

class Map {

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
            'content' => 'page/shops/map',
        ]);
        $content = $renderer->render('layout/shops', $page);


        return new Http\Response($content);
    }
}