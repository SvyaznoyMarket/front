<?php

namespace EnterAggregator;

use Closure;
use Enter\Curl;
use Enter\Logging;
use Enter\Routing;
use Enter\Http;
use Enter\Helper;
use EnterRepository as Repository;
use Enter\Mustache\Loader\FilesystemAliasLoader;

class Service {
    /** @var Closure */
    protected $configHandler;

    public function __construct(Closure $configHandler) {
        $this->configHandler = $configHandler;
    }

    /**
     * @return string
     */
    public function getRequestId() {
        static $instance;

        if (!$instance) {
            $instance = uniqid();
        }

        return $instance;
    }

    /**
     * @throws \Exception
     * @return Config
     */
    public function getConfig() {
        static $instance;

        if (!$instance) {
            $instance = new Config();
            call_user_func_array($this->configHandler, [$instance]);
        }

        return $instance;
    }

    /**
     * @return \StdClass
     */
    public function getDebugContainer() {
        static $instance;

        if (!$instance) {
            $instance = new \StdClass();
        }

        return $instance;
    }

    /**
     * @return Logging\Logger
     */
    public function getLogger() {
        static $instance;

        if (!$instance) {
            $config = $this->getConfig()->logger;

            $appenders = [];
            if ($config->fileAppender->enabled) {
                $appenders[] = new Logging\FileAppender($config->fileAppender->file);
            }

            $instance = new Logging\Logger($appenders, null, [
                '_id' => $this->getRequestId(),
            ]);
        }

        return $instance;
    }

    /**
     * @return Curl\Client
     */
    public function getCurl() {
        static $instance;

        if (!$instance) {
            $applicationConfig = $this->getConfig();

            $config = new Curl\Config();
            $config->encoding = 'gzip,deflate'; // важно!
            $config->httpheader = ['X-Request-Id: ' . $this->getRequestId(), 'Expect:'];
            $config->retryTimeout = $applicationConfig->curl->retryTimeout;
            $config->retryCount = $applicationConfig->curl->retryCount;
            $config->debug = $applicationConfig->debugLevel > 0;

            $instance = new Curl\Client($config);
            $instance->setLogger($this->getLogger());
        }

        return $instance;
    }

    /**
     * @return \Mustache_Engine
     */
    public function getMustacheRenderer() {
        static $instance;

        if (!$instance) {
            $config = $this->getConfig()->mustacheRenderer;

            require_once $config->dir . '/src/Mustache/Autoloader.php';
            \Mustache_Autoloader::register();

            $instance = new \Mustache_Engine([
                'template_class_prefix' => $config->templateClassPrefix,
                'cache'                 => $config->cacheDir,
                'loader'                => new \Mustache_Loader_FilesystemLoader($config->templateDir),
                /*
                'partials_loader'       => new \Mustache_Loader_CascadingLoader([
                     new FilesystemAliasLoader($config->templateDir),
                     new \Mustache_Loader_FilesystemLoader($config->templateDir),
                ]),
                */
                'partials_loader'       => new FilesystemAliasLoader($config->templateDir),
                'escape'                => $config->checkEscape
                    ? function($value) {
                        if ((null !== $value) && !is_scalar($value)) {
                            throw new \Exception('Неверное значение ' . json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                        }

                        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                    }
                    : function($value) {
                        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                    }
                ,
                'charset'               => 'UTF-8',
                //'logger'                => null,
                'logger'                => new \Mustache_Logger_StreamLogger('php://stderr'),
            ]);
        }

        return $instance;
    }

    /**
     * @return Routing\Router
     */
    public function getRouter() {
        static $instance;

        if (!$instance) {
            $applicationConfig = $this->getConfig();

            $config = new Routing\Config();
            $config->routeClassPrefix = $applicationConfig->router->classPrefix;
            $config->routes = $applicationConfig->router->routeFile ? json_decode(file_get_contents($applicationConfig->router->routeFile), true) : [];

            $instance = new Routing\Router($config);
        }

        return $instance;
    }

    /**
     * @return Http\Session
     */
    public function getSession() {
        $key = 'enter.http.session';

        if (!isset($GLOBALS[$key])) {
            $applicationConfig = $this->getConfig();

            $config = new Http\Session\Config();
            $config->name = $applicationConfig->session->name;
            $config->cookieLifetime = $applicationConfig->session->cookieLifetime;
            $config->cookieDomain = $applicationConfig->session->cookieDomain;
            $config->flashKey = $applicationConfig->session->flashKey;

            $instance = new Http\Session($config);
            try {
                $instance->start();
            } catch (\Exception $e) {
                $this->getLogger()->push(['type' => 'error', 'error' => $e, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['fatal', 'session']]);
            }

            $GLOBALS[$key] = $instance;
        }

        return $GLOBALS[$key];
    }

    /**
     * @return Repository\AbTest
     */
    public function getAbTest() {
        static $instance;

        if (!$instance) {
            $instance = new Repository\AbTest();
        }

        return $instance;
    }

    /**
     * @return Helper\Date
     */
    public function getDateHelper() {
        static $instance;

        if (!$instance) {
            $instance = new Helper\Date();
        }

        return $instance;
    }

    /**
     * @return Helper\Price
     */
    public function getPriceHelper() {
        static $instance;

        if (!$instance) {
            $instance = new Helper\Price();
        }

        return $instance;
    }

    /**
     * @return Helper\Translate
     */
    public function getTranslateHelper() {
        static $instance;

        if (!$instance) {
            $instance = new Helper\Translate();
        }

        return $instance;
    }

    /**
     * @return Helper\Url
     */
    public function getUrlHelper() {
        static $instance;

        if (!$instance) {
            $instance = new Helper\Url();
        }

        return $instance;
    }

    /**
     * @return Helper\Template
     */
    public function getTemplateHelper() {
        static $instance;

        if (!$instance) {
            $instance = new Helper\Template();
        }

        return $instance;
    }

    /**
     * @param Config $config
     * @return Config|\StdClass
     */
    protected function loadConfigFromJsonFile(Config $config) {
        if ($config->editable && $config->cacheDir) {
            $configPath = $config->cacheDir . '/config.json';
            $configFile = basename($configPath);

            $path = '';
            foreach (explode('/', trim($configPath, '/')) as $dir) {
                $path .= '/' . $dir;
                if ($configFile == $dir) {
                    if (is_readable($configPath)) {
                        if ($data = json_decode(file_get_contents($configPath))) {
                            $config = $data;
                        }
                    } else {
                        file_put_contents($configPath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    }
                } else {
                    if (!is_dir($path)) {
                        mkdir($path);
                    } else {
                        continue;
                    }
                }
            }
        }

        return $config;
    }
}