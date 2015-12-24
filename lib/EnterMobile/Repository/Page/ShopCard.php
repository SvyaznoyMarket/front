<?php

namespace EnterMobile\Repository\Page;

use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Partial;
use EnterMobile\Model\Page\ShopCard as Page;
use EnterAggregator\CurlTrait;
use EnterMobile\ConfigTrait;
use EnterMobile\TemplateRepositoryTrait;


class ShopCard {
    use LoggerTrait,
        TemplateHelperTrait,
        RouterTrait,
        CurlTrait,
        ConfigTrait,
        TemplateRepositoryTrait;

    /**
     * @param Page $page
     * @param ShopCard\Request $request
     */
    public function buildObjectByRequest(Page $page, ShopCard\Request $request) {
        (new Repository\Page\DefaultPage)->buildObjectByRequest($page, $request);

        $router = $this->getRouter();
        $templateHelper = $this->getTemplateHelper();
        $helper = new \Enter\Helper\Template();
        $page->dataModule = 'shopcard';

        $pointRepository = new \EnterRepository\Point();

        $point = $request->pointDescription['points'][0];
        $partner = $request->pointDescription['partners'][0];

        $partnerMedia = $pointRepository->getMedia($partner['slug'], ['logo', 'marker']);

        $medias = $point['medias'];
        $images = [];

        foreach ($medias as $media) {
            foreach ($media['sources'] as $source) {
                if ($source['type'] != 'shop_big') continue;
                $images[] = $source['url'];
            }
        }

        $result =
        [
            'group' => [
                'id' => $partner['slug'],
                'name' => $partner['name'],
                'media' => $pointRepository->getMedia($partner['slug'], ['logo', 'marker']),
            ],
            'address' => $point['address'],
            'regime' => $point['working_time'],
            'longitude' => isset($point['location'][0]) ? $point['location'][0] : null,
            'latitude' => isset($point['location'][1]) ? $point['location'][1] : null,
            'subway' => [[
                'name' => isset($point['subway']['name']) ? $point['subway']['name'] : null,
                'line' => [
                    'name' => isset($point['subway']['line_name']) ? $point['subway']['line_name'] : null,
                    'color' => isset($point['subway']['line_color']) ? $point['subway']['line_color'] : null,
                ],
            ]],
            'description' => isset($point['description']) ? $helper->unescape(strip_tags($point['description'])) : '',
            'phone' => isset($point['phone']) ? $point['phone'] : '',
            'walkWay' => isset($point['way_walk']) ? $helper->unescape(strip_tags($point['way_walk'])) : '',
            'carWay' => isset($point['way_auto']) ? $helper->unescape(strip_tags($point['way_auto'])) : '',
            'images' => $images,
            'logo' => (new \EnterRepository\Media())->getSourceObjectByList($partnerMedia->photos, 'logo', '100x100')->url,
            'marker' => (new \EnterRepository\Media())->getSourceObjectByList($partnerMedia->photos, 'marker', '31x40')->url
        ];

        $page->content->pointDescription = $result;

        if ($request->httpRequest->server['HTTP_REFERER']) {
            $backLink = $request->httpRequest->server['HTTP_REFERER'];
        } else {
            $backLink = $router->getUrlByRoute(
                new Routing\Shop\Index()
            );
        }

        $page->headerSwitchLink = [
            'backLink' => $backLink
        ];

        // шаблоны mustache
        $this->getTemplateRepository()->setListForPage($page, []);

        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}