<?php

namespace EnterMobile\Repository\Page\User;

use EnterAggregator\CurlTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\TemplateHelperTrait;
use EnterMobile\TemplateRepositoryTrait;
use EnterMobile\ConfigTrait;
use EnterMobile\Routing;
use EnterMobile\Repository;
use EnterMobile\Model;
use EnterMobile\Model\Partial;
use EnterMobile\Model\Page\User\Address\Index as Page;

class Address {
    use LoggerTrait,
        TemplateHelperTrait,
        RouterTrait,
        CurlTrait,
        ConfigTrait,
        TemplateRepositoryTrait;

    /**
     * @param Page $page
     * @param Address\Request $request
     */
    public function buildObjectByRequest(Page $page, Address\Request $request) {
        (new Repository\Page\User\DefaultPage)->buildObjectByRequest($page, $request);

        $templateHelper = $this->getTemplateHelper();
        $router = $this->getRouter();

        $page->title = 'Личный кабинет';

        $page->dataModule = 'user';

        foreach ($request->addresses as $addressModel) {
            $regionModel = ($addressModel->regionId && isset($request->regionsById[$addressModel->regionId])) ? $request->regionsById[$addressModel->regionId] : null;

            $region = $regionModel ? ['name'  => $regionModel->name] : false;
            $street = $addressModel->street ? $addressModel->street . ' ' . (($addressModel->streetType && (false === strpos($addressModel->street, $addressModel->streetType . '.'))) ? $addressModel->streetType : '') : '';
            $building = ($addressModel->building ? (!empty($addressModel->buildingType) ? ($addressModel->buildingType . ' ') : 'д. ') : '') . $addressModel->building;
            $apartment = $addressModel->apartment ? 'кв. ' . $addressModel->apartment : '';

            $page->content->addresses[] = [
                'region'    => $region,
                'street'    => $street,
                'building'  => $building,
                'apartment' => $apartment,
                'deleteUrl' => $router->getUrlByRoute(new Routing\User\Address\Delete(), ['addressId' => $addressModel->id]),
                'dataValue' => $templateHelper->json([
                    'deleteUrl' => $router->getUrlByRoute(new Routing\User\Address\Delete()),
                    'address'   => [
                        'id'        => $addressModel->id,
                        'region'    => $region,
                        'street'    => $street,
                        'building'  => $building,
                        'apartment' => $apartment,
                    ],
                ]),
            ];
        }

        // шаблоны mustache
        $this->getTemplateRepository()->setListForPage($page, [
            [
                'id'   => 'tpl-modalWindow',
                'name' => 'partial/private/popup',
            ],
            [
                'id'   => 'tpl-deleteForm',
                'name' => 'page/private/address/delete-form',
            ],
        ]);

        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}