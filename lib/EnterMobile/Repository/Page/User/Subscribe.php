<?php

namespace EnterMobile\Repository\Page\User;

use EnterAggregator\CurlTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterMobile\ConfigTrait;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Partial;
use EnterMobile\Model\Page\User\Subscribe\Index as Page;
use EnterMobile\TemplateRepositoryTrait;

class Subscribe {
    use LoggerTrait,
        TemplateHelperTrait,
        RouterTrait,
        CurlTrait,
        ConfigTrait,
        TemplateRepositoryTrait;

    /**
     * @param Page $page
     * @param Subscribe\Request $request
     */
    public function buildObjectByRequest(Page $page, Subscribe\Request $request) {
        (new Repository\Page\User\DefaultPage)->buildObjectByRequest($page, $request);

        $router = $this->getRouter();

        $templateHelper = $this->getTemplateHelper();

        $page->title = 'Личный кабинет';

        $page->dataModule = 'user';

        $setUrl = $router->getUrlByRoute(new Routing\User\Subscribe\Set());
        $deleteUrl = $router->getUrlByRoute(new Routing\User\Subscribe\Delete());

        foreach ($request->channelsById as $channelModel) {
            $subscribeModels = isset($request->subscriptionsGroupedByChannel[$channelModel->id]) ? $request->subscriptionsGroupedByChannel[$channelModel->id] : null;
            if (!$subscribeModels) continue;

            $page->content->subscribes[] = [
                'name'      => $channelModel->name,
                'elementId' => sprintf('channel-%s', md5(json_encode($channelModel, JSON_UNESCAPED_UNICODE))),
                'checked'   => (bool)$subscribeModels,
                'setUrl'    => $setUrl,
                'deleteUrl' => $deleteUrl,
                'dataValue' => $templateHelper->json([
                    'subscribe' => [
                        'channel_id' => $channelModel->id,
                        'type'       => 'email',
                        'email'      => $request->user->email,
                    ],
                ]),
            ];
        }

        // шаблоны mustache
        $this->getTemplateRepository()->setListForPage($page, []);

        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}