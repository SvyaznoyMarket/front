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


class ShopCard {
    use LoggerTrait,
        TemplateHelperTrait,
        RouterTrait,
        CurlTrait,
        ConfigTrait;

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
        ];

        $page->content->pointDescription = $result;



        $redirect = [
            'redirect_to' => trim((string)($request->httpRequest->query['redirect_to'] ?: $request->httpRequest->data['redirect_to'])),
            'initial_redirect_to' => trim((string)($request->httpRequest->query['initial_redirect_to'] ?: $request->httpRequest->data['initial_redirect_to']))
        ];

        $page->headerSwitchLink = [
            'backLink' => $router->getUrlByRoute(
                $router->getRouteByPath($redirect['redirect_to']),
                [
                    'redirect_to' => $redirect['initial_redirect_to']
                ])
        ];

        // шаблоны mustache
        // ...

//        (new Repository\Template())->setListForPage($page, [
//            [
//                'id'       => 'tpl-product-slider',
//                'name'     => 'partial/product-slider/mainPage',
//                'partials' => [
//                    'partial/cart/flat_button',
//                ],
//            ],
//            [
//                'id'       => 'tpl-points-list',
//                'name'     => 'partial/shops/points'
//            ]
//        ]);

        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}