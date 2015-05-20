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

        // данные пользователя
        $defaultUserData = [
            'firstName' => null,
            'phone'     => null,
            'email'     => null,
        ];
        $userData = (array)(isset($request->data['user']) ? $request->data['user'] : []) + $defaultUserData;

        $errors = [];
        try {
            foreach ($userData as $field) {
                if (!array_key_exists($field, $defaultUserData)) {
                    unset($userData[$field]);
                    continue;
                }

                if (!is_string($userData[$field])) {
                    $userData[$field] = null;
                    continue;
                }

                $userData[$field] = trim($userData[$field]);
            }

            $userData['phone'] = preg_replace('/^\+7/', '8', $userData['phone']);

            if (!$userData['firstName']) {
                //$errors[] = ['field' => 'firstName', 'name' => 'Не указано имя'];
            }
            if (!$userData['phone']) {
                $errors[] = ['field' => 'phone', 'name' => 'Не указан телефон'];
            }
            if (!$userData['email']) {
                $errors[] = ['field' => 'email', 'name' => 'Не указан email'];
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
            $response = new Http\JsonResponse([
                'errors' => $errors,
            ]);
        } else {
            if ($errors) {
                $session->flashBag->set('orderForm.error', $errors);
            }

            $response = (new \EnterAggregator\Controller\Redirect())->execute(
                $router->getUrlByRoute(
                    $errors
                    ? new Routing\Order\Index()
                    : new Routing\Order\Delivery()
                ),
                302
            );
        }

        return $response;
    }
}