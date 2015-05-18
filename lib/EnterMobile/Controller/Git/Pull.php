<?php
namespace EnterMobile\Controller\Git;

use Enter\Http;
use EnterMobile\ConfigTrait;

class Pull {
    use ConfigTrait;

    /**
     * @param Http\Request $request
     * @throws \Exception
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        if (in_array($request->getHost(), ['www.m.enter.ru', 'm.enter.ru'])) {
            return (new \EnterMobile\Controller\Error\NotFound())->execute($request);
        }

        try {
            $result = shell_exec('cd "' . $this->getConfig()->dir . '" && (git fetch; git status; git pull; git status)');
        } catch (\Exception $e) {
            $result = (string)$e;
        }

        $result = str_replace('On branch', '<b>On branch</b>', $result);

        return new Http\Response('<pre>' . $result . '</pre>');
    }
}
