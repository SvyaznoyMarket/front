<?php
namespace EnterModel\Cart\Split;
use EnterModel as Model;

class Order {
    /** @var string|null */
    public $blockName;
    /** @var Order\Seller|null */
    public $seller;
    /** @var Order\Product[] */
    public $products = [];
    /** @var Order\Discount[] */
    public $discounts = [];
    /** @var array */
    public $actions = [];
    /** @var Order\Delivery|null */
    public $delivery;
    /** @var string|null */
    public $sum;
    /** @var string|null */
    public $prepaidSum;
    /** @var string|null */
    public $originalSum;
    /** @var \EnterModel\Cart\Split\Order\Prepayment|null */
    public $prepayment;
    /** @var string|null */
    public $paymentMethodId;
    /**
     * Данный элемент оставлен для совместимости MAPI 1.6 с версиями мобильных приложений.
     * @var null
     */
    public $paymentLabel;
    /** @var array */
    public $possibleDeliveryMethodTokens = [];
    /** @var Model\Cart\Split\Interval[] */
    public $possibleIntervals = [];
    /** @var array */
    public $possibleDays = [];
    /** @var \EnterModel\Cart\Split\Order\PaymentMethod[] */
    public $possiblePaymentMethods = [];
    /**
     * @deprecated Используйте self::$possiblePaymentMethods
     * @var array
     */
    public $possiblePaymentMethodIds = [];
    /** @var array */
    public $groupedPossiblePointIds = [];
    /** @var Model\Cart\Split\Order\Point[] */
    public $possiblePoints = [];
    /** @var string|null */
    public $comment;
    /** @var int */
    public $isOnlinePaymentAvailable;

    /**
     * @param array $data
     * @param $format
     */
    public function __construct($data = [], $format = true) {
        if (!empty($data['prepaid_sum'])) {
            $this->prepayment = new \EnterModel\Cart\Split\Order\Prepayment($data['prepaid_sum']);
        }

        $this->blockName = $data['block_name'] ? (string)$data['block_name'] : null;
        $this->seller = $data['seller'] ? new Order\Seller($data['seller']) : null;

        foreach ($data['products'] as $item) {
            $this->products[] = new Order\Product($item);
        }

        foreach ($data['discounts'] as $item) {
            $this->discounts[] = new Order\Discount($item);
        }

        $this->actions = (array)$data['actions'];

        $this->delivery = $data['delivery'] ? new Order\Delivery($data['delivery']) : null;
        $this->sum = $data['total_cost'] ? (string)$data['total_cost'] : null;
        $this->originalSum = $data['total_original_cost'] ? (string)$data['total_original_cost'] : null;
        $this->prepaidSum = !empty($data['prepaid_sum']) ? (string)$data['prepaid_sum'] : null;
        $this->paymentMethodId = $data['payment_method_id'] ? (string)$data['payment_method_id'] : null;
        $this->isOnlinePaymentAvailable = isset($data['is_online_payment_available']) ? $data['is_online_payment_available'] : null;
        foreach ($data['possible_deliveries'] as $token) {
            $this->possibleDeliveryMethodTokens[] = (string)$token;
        }
        foreach ($data['possible_intervals'] as $item) {
            $this->possibleIntervals[] = new Interval($item);
        }
        foreach ($data['possible_days'] as $day) {
            $this->possibleDays[] = (string)$day;
        }
        //$this->possibleDays = []; // FIXME: fixture
        if (isset($data['possible_payment_methods']) && is_array($data['possible_payment_methods'])) {
            foreach ($data['possible_payment_methods'] as $id) {
                if (in_array((string), ['10'])) continue;

                $this->possiblePaymentMethodIds[] = (string)$id;
                $this->possiblePaymentMethods[] = new Order\PaymentMethod(['id' => $id] + (isset($data['payment_methods'][$id]) ? $data['payment_methods'][$id] : []));
            }
        }

        foreach ($data['possible_points'] as $token => $ids) {
            foreach ($ids as $id) {
                $this->groupedPossiblePointIds[$token][] = (string)$id;
            }
        }
        foreach ($data['possible_point_data'] as $key => $items) {
            foreach ($items as $item) {
                $possiblePoint = new Order\Point($item);
                $possiblePoint->groupToken = $key;

                if ($format) {
                    $this->possiblePoints[] = $possiblePoint;
                } else {
                    $this->possiblePoints[$key][] = $possiblePoint;
                }
            }
        }
        $this->comment = $data['comment'] ? (string)$data['comment'] : null;
    }

    /**
     * @return array
     */
    public function dump() {
        $possiblePointsData = [];
        foreach ($this->possiblePoints as $possiblePoint) {
            $possiblePointsData[$possiblePoint->groupToken] = $possiblePoint->dump();
        }

        return [
            'block_name'               => $this->blockName,
            'seller'                   => $this->seller ? $this->seller->dump() : null,
            'products'                 => array_map(function(Order\Product $product) { return $product->dump(); }, $this->products),
            'discounts'                => array_map(function(Order\Discount $discount) { return $discount->dump(); }, $this->discounts),
            'actions'                  => $this->actions,
            'delivery'                 => $this->delivery ? $this->delivery->dump() : null,
            'total_cost'               => $this->sum,
            'total_original_cost'      => $this->originalSum,
            'payment_method_id'        => $this->paymentMethodId ? (int)$this->paymentMethodId : null,
            'possible_deliveries'      => $this->possibleDeliveryMethodTokens,
            'possible_intervals'       => array_map(function(Model\Cart\Split\Interval $interval) { return $interval->dump(); }, $this->possibleIntervals),
            'possible_days'            => array_map(function($day) { return (string)$day; }, $this->possibleDays),
            'possible_payment_methods' => $this->possiblePaymentMethodIds,
            'payment_methods'          => array_map(function(\EnterModel\Cart\Split\Order\PaymentMethod $possiblePaymentMethod) { return $possiblePaymentMethod->dump(); }, $this->possiblePaymentMethods),
            'possible_points'          => $this->groupedPossiblePointIds,
            'possible_point_data'      => $possiblePointsData,
            'comment'                  => $this->comment,
            'prepaid_sum'              => $this->prepayment ? (float)$this->prepayment->sum : 0,
        ];
    }
}
