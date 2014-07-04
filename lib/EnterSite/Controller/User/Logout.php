<?php

namespace EnterSite\Controller\User;

use Enter\Http;
use EnterSite\ConfigTrait;
use EnterSite\CurlClientTrait;
use EnterSite\RouterTrait;
use EnterSite\Controller;
use EnterSite\Repository;
use EnterCurlQuery as Query;
use EnterSite\Model;
use EnterSite\Model\Form;
use EnterSite\Routing;

class Logout {
    use ConfigTrait, CurlClientTrait, RouterTrait {
        ConfigTrait::getConfig insteadof CurlClientTrait, RouterTrait;
    }

    /**
     * @param Http\Request $request
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $router = $this->getRouter();

        // редирект
        $redirectUrl = !empty($request->query['redirect_to']) ? $request->query['redirect_to'] : null;
        if (!$redirectUrl) {
            $redirectUrl = $router->getUrlByRoute(new Routing\User\Login());
        }
        // http-ответ
        $response = (new Controller\Redirect())->execute($redirectUrl, 302);
        // сброс cookie
        (new \EnterRepository\User())->setTokenToHttpResponse(null, $response);

        return $response;
    }
}