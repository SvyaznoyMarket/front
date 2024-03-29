<?php

namespace EnterMobile\Controller;

use Enter\Http;
use Enter\Templating;
use Enter\Util;
use EnterMobile\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\MustacheRendererTrait;
use EnterMobile\Controller;

class Log {
    use ConfigTrait, LoggerTrait, MustacheRendererTrait;

    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $logger = $this->getLogger();

        $id = trim((string)$request->query['id']);
        if (!$id) {
            throw new \Exception('Не передан id');
        }

        $offset = (int)$request->query['offset'] ?: 600;
        if ($offset > 100000) {
            throw new \Exception('Слишком большой offset');
        }
        $before = (int)$request->query['before'];
        if ($before > 10000) {
            throw new \Exception('Слишком большой before');
        }

        // страница
        $page = [
            'dataDebug' => $this->getConfig()->debugLevel ? 'true' : '',
            'id'        => $id,
            'date'      => null,
            'messages'  => [],
        ];

        $command = sprintf('tail -n %s %s | grep "\"_id\":\"%s\""%s',
            $offset,
            $config->logger->fileAppender->file,
            $id,
            $before ? (' -B ' . $before) : ''
        );

        $result = shell_exec($command);

        $messages = [];
        foreach (explode(PHP_EOL, $result) as $line) {
            if (!$line) continue;

            $line = json_decode($line, true);
            if (isset($line['date'])) {
                if (!isset($page['date'])) $page['date'] = $line['date'];
                unset($line['date']);
            }

            // query
            if (isset($line['query']['response'])) {
                try {
                    $line['query']['response'] = Util\Json::toArray($line['query']['response']);
                } catch (\Exception $e) {
                    $logger->push(['type' => 'warn', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['debug']]);
                }
            }

            $messages[] = [
                'id'    => $line['time'],
                'color' => $id == $line['_id'] ? '#ffffcc' : '#ededed',
                'value' => json_encode($line, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            ];
        }
        $page['messages'] = $messages;

        // рендер
        $rendererConfig = new Templating\PhpClosure\Config();
        $rendererConfig->templateDir = $config->mustacheRenderer->templateDir;
        $renderer = new Templating\PhpClosure\Renderer($rendererConfig);
        $content = $renderer->render('page/log', $page);

        // http-ответ
        $response = new Http\Response($content);

        return $response;
    }
}