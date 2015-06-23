<?php

namespace EnterAggregator {
    class Config {
        /** @var string */
        public $version;
        /** @var array */
        public $applicationTags = [];
        /** @var string */
        public $dir;
        /** @var string */
        public $cacheDir;
        /** @var string */
        public $environment;
        /**
         * Уровень отладки: 0 - нет, 1 - по умолчанию, 2 - подробно
         * @var int
         */
        public $debugLevel;
        /**
         * Возможность редактировать конфигурацию и загружать ее из json-файла
         * @var bool
         */
        public $editable;
        /** @var string */
        public $hostname;
        /** @var Config\Logger */
        public $logger;
        /** @var Config\Router */
        public $router;
        /** @var Config\Session */
        public $session;
        /** @var Config\UserToken */
        public $userToken;
        /** @var Config\Region */
        public $region;
        /** @var Config\AbTest */
        public $abTest;
        /** @var Config\GoogleAnalytics */
        public $googleAnalytics;
        /** @var Config\GoogleTagManager */
        public $googleTagManager;
        /** @var Config\YandexMetrika */
        public $yandexMetrika;
        /** @var Config\MailRu */
        public $mailRu;
        /** @var Config\Credit */
        public $credit;
        /** @var Config\Partner */
        public $partner;
        /** @var Config\Curl */
        public $curl;
        /** @var Config\CoreService */
        public $coreService;
        /** @var Config\EventService */
        public $eventService;
        /** @var Config\CorePrivateService */
        public $corePrivateService;
        /** @var Config\SearchService */
        public $searchService;
        /** @var Config\ScmsService */
        public $scmsService;
        /** @var Config\CrmService */
        public $crmService;
        /** @var Config\AdminService */
        public $adminService;
        /** @var Config\ReviewService */
        public $reviewService;
        /** @var Config\ContentService */
        public $contentService;
        /** @var Config\InfoService */
        public $infoService;
        /** @var Config\RetailRocketService */
        public $retailRocketService;
        /** @var Config\MustacheRenderer */
        public $mustacheRenderer;
        /** @var array */
        public $mediaHosts = [];
        /** @var Config\Order */
        public $order;
        /** @var Config\Product */
        public $product;
        /** @var Config\ProductReview */
        public $productReview;
        /** @var Config\ProductPhoto */
        public $productPhoto;
        /** @var Config\Search */
        public $search;
        /** @var Config\Promo */
        public $promo;
        /** @var Config\ProductLabel */
        public $productLabel;

        public function __construct() {
            $this->logger = new Config\Logger();
            $this->router = new Config\Router();

            $this->session = new Config\Session();
            $this->userToken = new Config\UserToken();

            $this->abTest = new Config\AbTest();

            $this->googleAnalytics = new Config\GoogleAnalytics();
            $this->googleTagManager = new Config\GoogleTagManager();
            $this->yandexMetrika = new Config\YandexMetrika();
            $this->mailRu = new Config\MailRu();

            $this->region = new Config\Region();
            $this->credit = new Config\Credit();
            $this->partner = new Config\Partner();

            $this->curl = new Config\Curl();

            $this->coreService = new Config\CoreService();
            $this->eventService = new Config\EventService();
            $this->corePrivateService = new Config\CorePrivateService();
            $this->searchService = new Config\SearchService();
            $this->scmsService = new Config\ScmsService();
            $this->crmService = new Config\CrmService();
            $this->adminService = new Config\AdminService();
            $this->reviewService = new Config\ReviewService();
            $this->contentService = new Config\ContentService();
            $this->infoService = new Config\InfoService();
            $this->retailRocketService = new Config\RetailRocketService();

            $this->mustacheRenderer = new Config\MustacheRenderer();

            $this->order = new Config\Order();
            $this->product = new Config\Product();
            $this->productReview = new Config\ProductReview();
            $this->productPhoto = new Config\ProductPhoto();
            $this->search = new Config\Search();
            $this->promo = new Config\Promo();
            $this->productLabel = new Config\ProductLabel();
        }
    }
}

namespace EnterAggregator\Config {
    class Logger {
        /** @var Logger\FileAppender */
        public $fileAppender;

        public function __construct() {
            $this->fileAppender = new Logger\FileAppender();
        }
    }

    class Router {
        /** @var string */
        public $classPrefix;
        /**
         * Файл с маршрутами
         *
         * @var string
         */
        public $routeFile;
    }

    class Session {
        /** @var string */
        public $name;
        /** @var int */
        public $cookieLifetime;
        /** @var string */
        public $cookieDomain;
        /**
         * Ключ для параметра, который отвечает за хранение данных между ДВУМЯ http-запросами
         * @var string
         */
        public $flashKey;
    }

    class UserToken {
        /**
         * Кука авторизованного пользователя
         * @var string
         */
        public $authName;
    }

    class AbTest {
        /** @var string */
        public $cookieName;
    }

    class GoogleAnalytics {
        /** @var string */
        public $id;
        /** @var bool */
        public $enabled;
    }

    class GoogleTagManager extends CurlService {
        /** @var bool */
        public $enabled;
        /** @var string */
        public $id;
    }

    class YandexMetrika extends CurlService {
        /** @var bool */
        public $enabled;
        /** @var int */
        public $id;
    }

    class MailRu extends CurlService {
        /** @var bool */
        public $enabled;
        /** @var int */
        public $id;
    }

    class Region {
        /** @var string */
        public $defaultId;
        /** @var string */
        public $cookieName;
    }

    class Credit {
        /** @var string */
        public $cookieName;
        /** @var Credit\DirecCredit  */
        public $directCredit;
        /** @var Credit\Kupivkredit */
        public $kupivkredit;

        public function __construct() {
            $this->directCredit = new Credit\DirecCredit();
            $this->kupivkredit = new Credit\Kupivkredit();
        }
    }
    class Partner {
        /** @var string */
        public $cookieName;
        /** @var int */
        public $cookieLifetime;
        /** @var bool */
        public $enabled;
        /** @var Partner\Service */
        public $service;

        public function __construct() {
            $this->service = new Partner\Service();
        }
    }

    class Curl {
        /** @var int */
        public $queryChunkSize;
        /** @var bool */
        public $logResponse;
        /** @var float */
        public $timeout;
        /** @var float */
        public $retryTimeout;
        /** @var int */
        public $retryCount;
    }

    class CurlService {
        /** @var string */
        public $url;
        /** @var string */
        public $user;
        /** @var string */
        public $password;
        /** @var float */
        public $timeout;

        public function __construct() {}
    }

    class CoreService extends CurlService {
        /** @var string */
        public $clientId;
        /** @var bool */
        public $debug;
    }

    class EventService extends CurlService {
        /** @var bool */
        public $enabled;
        /** @var string */
        public $clientId;
    }

    class CorePrivateService extends CoreService {
    }

    class SearchService extends CurlService {
        /** @var string */
        public $clientId;
        /** @var bool */
        public $debug;
    }

    class ScmsService extends CurlService {
    }

    class CrmService extends CurlService {
        /** @var string */
        public $clientId;
        /** @var bool */
        public $debug;
    }

    class AdminService extends CurlService {
    }

    class ReviewService extends CurlService {
    }

    class ContentService extends CurlService {
    }

    class InfoService extends CurlService {
    }

    class RetailRocketService extends CurlService {
        /** @var string */
        public $account;
    }

    class MustacheRenderer {
        /** @var string */
        public $dir;
        /** @var string */
        public $templateDir;
        /** @var string */
        public $cacheDir;
        /** @var string */
        public $templateClassPrefix;
        /**
         * Проверять передаваемые в escape-функцию значения или нет
         *
         * @var bool
         */
        public $checkEscape;
    }

    class Order {
        /** @var string */
        public $splitSessionKey;
    }

    class Product {
        /**
         * Количество элементов на страницу
         * @var int
         */
        public $itemPerPage;
        /**
         * Количество элементов в слайдере
         * @var int
         */
        public $itemsInSlider;
    }

    class ProductPhoto {
        /**
         * @var array
         */
        public $urlPaths = [];
    }

    class ProductReview {
        /** @var bool */
        public $enabled;
        /**
         * Количество элементов в карточке товара
         * @var int
         */
        public $itemsInCard;
    }

    class Search {
        /** @var int */
        public $minPhraseLength;
    }

    class Promo {
        /**
         * @deprecated
         * @var int
         */
        public $typeId;
        /**
         * @var array
         */
        public $urlPaths = [];
    }

    class ProductLabel {
        /** @var array */
        public $urlPaths = [];
    }
}

namespace EnterAggregator\Config\Logger {
    abstract class BaseAppender {
        /** @var bool */
        public $enabled;
    }

    class FileAppender extends BaseAppender {
        /** @var string */
        public $file;
    }
}

namespace EnterAggregator\Config\Partner {
    class Service {
        /** @var Service\Actionpay */
        public $actionpay;
        /** @var Service\Criteo */
        public $criteo;
        /** @var Service\Sociomantic */
        public $sociomantic;
        /** @var Service\GoogleRetargeting */
        public $googleRetargeting;
        /** @var Service\Cityads */
        public $cityads;

        public function __construct() {
            $this->actionpay = new Service\Actionpay();
            $this->criteo = new Service\Criteo();
            $this->sociomantic = new Service\Sociomantic();
            $this->googleRetargeting = new Service\GoogleRetargeting();
            $this->cityads = new Service\Cityads();
        }
    }
}

namespace EnterAggregator\Config\Partner\Service {
    abstract class PartnerConfig {
        /** @var bool */
        public $enabled;
    }

    class Actionpay extends PartnerConfig {}

    class Criteo extends PartnerConfig {
        /** @var int */
        public $account;
    }

    class Sociomantic extends PartnerConfig {}

    class GoogleRetargeting extends PartnerConfig {}

    class Cityads extends PartnerConfig {}
}

namespace EnterAggregator\Config\Credit {
    use EnterAggregator\Config\CurlService;

    class DirecCredit {
        /** @var bool */
        public $enabled;
        /** @var int */
        public $minPrice;
        /** @var string */
        public $partnerId;
    }

    class Kupivkredit extends CurlService {
        /** @var bool */
        public $enabled;
        /** @var string */
        public $partnerId;
        /** @var string */
        public $secretPhrase;
        /** @var string */
        public $channel;
    }
}