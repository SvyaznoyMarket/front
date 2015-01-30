<?php

namespace EnterTerminal\Controller\Admin\Config;

use Enter\Http;
use Enter\Templating;
use EnterTerminal\ConfigTrait;
use EnterQuery as Query;
use EnterTerminal\Controller;

class Update {
    use ConfigTrait;

    public function execute(Http\Request $request) {
        $config = $this->getConfig();

        if (!$config->editable) {
            return (new Controller\Error\NotFound())->execute($request);
        }

        $formData = @$_POST['form']['config'];
        $formData = json_decode($formData, true);


        if (empty($formData) || !is_array($formData)) {
            throw new \Exception('Форма не передана');
        }

        $configData = json_decode(json_encode($config), true);
        $configData = array_merge($configData, $formData);

        $configPath = $config->cacheDir . '/config.json';
        file_put_contents($configPath, json_encode($configData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // http-ответ
        return (new \EnterAggregator\Controller\Redirect())->execute('/' . $config->version . '/Admin/Config', 302);
    }
}