<?php

namespace EnterMobile\Repository\Page\Shops;

use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Partial;
use EnterMobile\Model\Page\Shops\Index as Page;
use EnterAggregator\CurlTrait;
use EnterMobile\ConfigTrait;


class Map {
    use LoggerTrait,
        TemplateHelperTrait,
        RouterTrait,
        CurlTrait,
        ConfigTrait;

    /**
     * @param Page $page
     * @param Index\Request $request
     */
    public function buildObjectByRequest(Page $page, Index\Request $request) {
        (new Repository\Page\DefaultPage)->buildObjectByRequest($page, $request);
        $router = $this->getRouter();


        $page->dataModule = 'shops.map';
        $pointRepository = new \EnterRepository\Point();

        $result = [
            'points' => array_values($request->points['points']),
            'partners' => $request->points['partners']
        ];


        $partners = [];

        foreach ($result['partners'] as $key => $partner) {
            $partnerMedia = $pointRepository->getMedia($partner['slug'], ['logo', 'marker']);

            $partners[$partner['slug']] = [
                'id' => $partner['slug'],
                'name' => $partner['name'],
                'logo' => (new \EnterRepository\Media())->getSourceObjectByList($partnerMedia->photos, 'logo', '100x100')->url,
                'marker' => (new \EnterRepository\Media())->getSourceObjectByList($partnerMedia->photos, 'marker', '31x40')->url
            ];
        }

        $result['points'] = array_map(function($point) use(&$pointRepository, &$partners) {
            return [
                'group' => ['id' => $point['partner']],
                'ui' => $point['uid'],
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
                'logo' => $partners[$point['partner']]['logo'],
                'marker' => $partners[$point['partner']]['marker'],
                'name' => $partners[$point['partner']]['name']
            ];
        }, $result['points']);

        $result['points'] = array_values(array_filter($result['points'], function($item) {
            return !empty($item['latitude']) && !empty($item['longitude']);
        }));


        $page->content->points = $result['points'];

        $backLink = trim((string)($request->httpRequest->query['redirect_to'] ?: $request->httpRequest->data['redirect_to']));
        if (!$backLink) {
            $backLink = $router->getUrlByRoute(new Routing\Index());
        }

        $page->headerSwitchLink = [
            'name' => 'Список',
            'link' => $router->getUrlByRoute(new Routing\Shop\Index(),
                ['redirect_to' => $backLink]
            )
        ];

        // шаблоны mustache
        // ...

        (new Repository\Template())->setListForPage($page, [
//            [
//                'id'       => 'tpl-product-slider',
//                'name'     => 'partial/product-slider/mainPage',
//                'partials' => [
//                    'partial/cart/flat_button',
//                ],
//            ],
            [
                'id'       => 'tpl-points-list',
                'name'     => 'partial/shops/points'
            ]
        ]);

        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}