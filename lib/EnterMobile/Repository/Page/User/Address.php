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
use EnterMobile\Model\Page\User\Address\Index as Page;

class Address {
    use LoggerTrait,
        TemplateHelperTrait,
        RouterTrait,
        CurlTrait,
        ConfigTrait;

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

            $page->content->addresses[] = [
                'shownStreet'   =>
                    $addressModel->street
                    ? (($addressModel->streetType && (false === strpos($addressModel->street, $addressModel->streetType . '.'))) ? ($addressModel->streetType . '.') : '') . $addressModel->street
                    : ''
                ,
                'shownBuilding' =>
                    ($addressModel->building ? (!empty($addressModel->buildingType) ? ($addressModel->buildingType . ' ') : 'д. ') : '') . $addressModel->building
                ,
                'apartment'     => $addressModel->apartment,
                'region'        =>
                    $regionModel
                    ? [
                        'name' => $regionModel->name,
                    ]
                    : false
                ,
                'deleteUrl'     => false,
            ];
        }

        // шаблоны mustache
        // ...

        (new Repository\Template())->setListForPage($page, [

        ]);

        //die(json_encode($page, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}