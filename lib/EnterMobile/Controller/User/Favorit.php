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

class Favorit {

    use ConfigTrait, CurlTrait, MustacheRendererTrait, DebugContainerTrait;

    public function execute(Http\Request $request) {

        $page = new Page();

        $page->title = 'Заголовок';
        // рендер
        $renderer = $this->getRenderer();
        $renderer->setPartials([
            'content' => 'page/private/favorit'
        ]);

        $content = $renderer->render('layout/footerless', $page);

        return new Http\Response($content);
    }
}