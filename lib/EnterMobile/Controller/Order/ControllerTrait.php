<?php

namespace EnterMobile\Controller\Order;

use Enter\Http;
use EnterMobile\ConfigTrait;

trait ControllerTrait {
    use ConfigTrait;

    /**
     * @param Http\Request $request
     * @return string
     */
    public function getCartSessionKeyByHttpRequest(Http\Request $request) {
        return (!empty($request->query['shopId'])) ? $this->getConfig()->cart->quickSessionKey : $this->getConfig()->cart->sessionKey;
    }
}