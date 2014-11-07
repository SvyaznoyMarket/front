<?php

namespace EnterTerminal\Controller\Admin;

use Enter\Http;
use Enter\Templating;
use EnterTerminal\ConfigTrait;
use EnterQuery as Query;
use EnterTerminal\Controller;

class Config {
    use ConfigTrait;

    public function execute(Http\Request $request) {
        $config = $this->getConfig();

        if (!$config->editable) {
            return (new Controller\Error\NotFound())->execute($request);
        }

        $configData = [];
        foreach ([
            'coreService',
            'scmsService',
            'curl',
        ] as $key) {
            $configData[$key] = $config->{$key};
        }

        $page = [
            'title' => $config->applicationName,
            'updateForm'  => [
                'action' => '/Admin/Config/Update',
                'field'  => [
                    'config' => [
                        'name'  => 'form[config]',
                        'value' => json_encode($configData, JSON_PRETTY_PRINT, JSON_UNESCAPED_UNICODE),
                    ],
                ],
            ],
            'resetForm'   => null,
        ];

        // рендер
        $rendererConfig = new Templating\PhpClosure\Config();
        $rendererConfig->templateDir = $config->dir . '/template';
        $renderer = new Templating\PhpClosure\Renderer($rendererConfig);
        $content = $renderer->render('page/admin/config', $page);

        // http-ответ
        return new Http\Response($content);
    }
}