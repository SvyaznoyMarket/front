<?php

namespace EnterMobile\Repository\Partial;

class Point {
    /**
     * @param string $type
     * @return string
     */
    public function getIconByType($type) {
        $icon = null;

        switch ($type) {
            case 'self_partner_pickpoint_pred_supplier':
            case 'self_partner_pickpoint':
            case 'pickpoint':
                $icon = 'pickpoint';
                break;
            case 'self_partner_svyaznoy_pred_supplier':
            case 'self_partner_svyaznoy':
            case 'shops_svyaznoy':
            case 'svyaznoy':
                $icon = 'svyaznoy';
                break;
            case 'self_partner_hermes_pred_supplier':
            case 'self_partner_hermes':
            case 'hermes':
                $icon = 'hermes';
                break;
            default:
                $icon = 'enter';
        }

        return $icon . '.png';
    }

    /**
     * @param string $groupName
     * @return string
     */
    public function translateGroupName($groupName) {
        return strtr($groupName, [
            'Магазин'           => 'Магазин Enter',
            'Магазин "Связной"' => 'Связной',
            'Постамат'          => 'Постамат PickPoint',
            'Hermes DPD'        => 'Постамат Hermes-DPD',
        ]);
    }

    public function getGroupNameByType($type) {
        $return = null;

        switch ($type) {
            case 'pickpoint':
                $return = 'Постамат PickPoint';
                break;
            case 'svyaznoy':
                $return = 'Связной';
                break;
            case 'hermes':
                $return = 'Постамат Hermes-DPD';
                break;
            default:
                $return = 'Магазин Enter';
        }

        return $return;
    }
}