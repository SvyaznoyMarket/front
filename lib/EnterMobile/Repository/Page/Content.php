<?php

namespace EnterMobile\Repository\Page;

use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Partial;

class Content {
    use ConfigTrait, LoggerTrait, RouterTrait, TemplateHelperTrait;

    /**
     * @param Model\Page\Content $page
     * @param Content\Request $request
     */
    public function buildObjectByRequest(Model\Page\Content $page, Content\Request $request) {
        (new Repository\Page\DefaultPage)->buildObjectByRequest($page, $request);

        $page->title = $request->title;
        $page->content = $request->content;

        // TODO сделать настройки для партнёрских скриптов
        // partner

        /*
        try {
            $page->partners = (new Repository\Partial\Partner())->getListForIndex($request);
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['partner']]);
        }
        */

        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}