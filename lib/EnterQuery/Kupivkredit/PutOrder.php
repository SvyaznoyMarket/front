<?php

namespace EnterQuery\Kupivkredit;

use Enter\Curl\Query;
use Enter\Util;
use Enter1C\ConfigTrait;
use EnterAggregator\LoggerTrait;
use EnterQuery\Url;
use EnterModel as Model;

class PutOrder extends Query {
    use ConfigTrait, LoggerTrait;

    /** @var array|null */
    protected $result;

    /**
     * @param Model\Order $order
     * @param \EnterModel\User $user
     * @param Model\Product[] $productsById
     */
    public function __construct(Model\Order $order, Model\User $user, array $productsById) {
        $config = $this->getConfig()->credit->kupivkredit;

        $this->url = new Url();
        $this->url->path = 'formnew/quickshort';

        // data
        $userPhone = $user->phone;
        $matches = [];
        if (preg_match( '/^\d(\d{3})(\d{3})(\d{4})$/', $userPhone, $matches)) {
            $userPhone = '+7(' . $matches[1] . ')' . $matches[2] . '-' . $matches[3];
        }

        $orderData = [
            'items'          => [],
            'details'        => [
                'firstname'  => $user->firstName,
                'lastname'   => $user->lastName,
                'middlename' => $user->middleName,
                'email'      => $user->email,
                'cellphone'  => $userPhone, // '+7(902)712-1141'
            ],
            'partnerId'      => $config->partnerId,
            'merchantId'     => '', // ID Партнера в системе Банка, для которого создается заявка. Используется только в случаях создания заявки для других Партнеров
            'partnerOrderId' => $order->number,
            'deliveryType'   => '',
        ];

        foreach ($order->product as $orderProduct) {
            $product = isset($productsById[$orderProduct->id]) ? $productsById[$orderProduct->id] : null;
            if (!$product) {
                $this->getLogger()->push(['type' => 'error', 'message' => 'Товар не найден', 'product.id' => $orderProduct->id, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['kupivkredit']]);
                continue;
            }

            $categoryNames = [];
            if ($product->category) {
                foreach ($product->category->ascendants as $ascendant) {
                    $categoryNames[] = $ascendant->name;
                }

                $categoryNames[] = $product->category->name;
            }

            $orderData['items'][] = [
                'title'    => $product->name,
                'category' => implode(' / ', $categoryNames),
                'qty'      => $orderProduct->quantity,
                'price'    => $orderProduct->price,
                'sum'      => $orderProduct->sum,
            ];
        }

        // Получение base64-строки из массива заказа
        $base64OrderData = base64_encode(json_encode($orderData));

        /* Получение подписи сообщения */
        $sig = $this->getDataSignature($base64OrderData, $config->secretPhrase);

        $this->data = [
            'channel' => $config->channel,
            'name'    => $user->firstName,
            'phone'   => preg_replace('/^8/', '7', $user->phone),
            'order'   => $base64OrderData,
            'sig'     => $sig,
        ];

        // TODO: вынести в KupivkreditQueryTrait
        $config = $this->getConfig()->credit->kupivkredit;

        $this->dataEncoder = null;
        $this->url->prefix = $config->url;
        $this->timeout = $config->timeout;
        /*
        $this->headers = [
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8'
        ];
        */

    }

    /**
     * @param $response
     * @return array|void
     */
    public function callback($response) {
        if ($this->getConfig()->curl->logResponse) {
            $this->response = $response;
        }

        $data = null;
        try {
            $data = Util\Json::toArray($response);

            if (!isset($data['status']) || ('success' != $data['status'])) {
                $this->getLogger()->push(['type' => 'error', 'data' => $data, 'sender' => __FILE__ . ' ' .  __LINE__, 'tag' => ['kupivkredit']]);

                throw new \Exception('Заявка не подтверждена');
            }
        } catch (\Exception $e) {
            $this->error = $e;
        }

        $this->result = $data;
    }

    /**
     * @param $message
     * @param $secretPhrase
     * @return string
     */
    private function getDataSignature($message, $secretPhrase) {
        $message = $message . $secretPhrase;
        $result = md5($message) . sha1($message);
        for ($i = 0; $i < 1102; $i++) {
            $result = md5($result);
        }

        return $result;
    }
}