<?php

namespace EnterMobile\Controller\Order;

use Enter\Http;
use EnterMobile\ConfigTrait;
use EnterAggregator\CurlTrait;
use EnterAggregator\LoggerTrait;
use EnterAggregator\SessionTrait;
use EnterAggregator\RouterTrait;
use EnterAggregator\DebugContainerTrait;
use EnterModel as Model;
use EnterQuery as Query;
use EnterMobile\Routing;
use EnterMobile\Controller;
use EnterMobile\Repository;

class SetUser {
    use ConfigTrait, CurlTrait, SessionTrait, LoggerTrait, RouterTrait, DebugContainerTrait;

    /**
     * @param Http\Request $request
     * @return Http\Response
     */
    public function execute(Http\Request $request) {
        $config = $this->getConfig();
        $session = $this->getSession();
        $router = $this->getRouter();

        // ид магазина
        $shopId = is_scalar($request->query['shopId']) ? (string)$request->query['shopId']: null;

        // данные пользователя
        $userData = (array)(isset($request->data['user']) ? $request->data['user'] : []) + [
            'firstName'  => null,
            'phone'      => null,
            'email'      => null,
            'bonusCards' => [],
        ];

        if (11 === mb_strlen($userData['phone']) && (0 === strpos($userData['phone'], '8'))) {
            $userData['phone'] = preg_replace('/^8/', '+7', $userData['phone']);
        }

        $errors = [];
        try {
            if (!$userData['firstName']) {
                //$errors[] = ['field' => 'firstName', 'name' => 'Не указано имя'];
            }

            $phone = preg_replace('/^\+7/', '8', $userData['phone']);
            $phone = preg_replace('/[^\d]/', '', $phone);
            if (!$phone) {
                $errors[] = ['field' => 'phone', 'name' => 'Не указан телефон'];
            } else if (11 !== strlen($phone)) {
                $errors[] = ['field' => 'phone', 'name' => 'Неверный номер телефона'];
            }
            if (!$userData['email']) {
                $errors[] = ['field' => 'email', 'name' => 'Не указан email'];
            }
            if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = ['field' => 'email', 'name' => 'Неправильный email'];
            }

            if (count($errors) > 0) {
                throw new \Exception('Не заполнены обязательные поля');
            }

            $session->set($config->order->userSessionKey, $userData);
        } catch (\Exception $e) {
            $this->getLogger()->push(['type' => 'error', 'error' => $e, 'errors' => $errors, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['order', 'critical']]);
        }

        // http-ответ
        $response = null;
        if ($request->isXmlHttpRequest()) {
            $responseData = [
                'errors' => $errors,
            ];
            if (!$errors) {
                $responseData['redirect'] = $router->getUrlByRoute(new Routing\Order\Delivery(), ['shopId' => $shopId]);
            }

            $response = new Http\JsonResponse($responseData);
        } else {
            if ($errors) {
                $session->flashBag->set('orderForm.error', $errors);
            }

            $response = (new \EnterAggregator\Controller\Redirect())->execute(
                $router->getUrlByRoute(
                    $errors ? new Routing\Order\Index() : new Routing\Order\Delivery(),
                    ['shopId' => $shopId]
                ),
                302
            );
        }

        return $response;
    }
}