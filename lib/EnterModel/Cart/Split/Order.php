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
    public $originalSum;
    /** @var string|null */
    public $paymentMethodId;
    /** @var array */
    public $possibleDeliveryMethodTokens = [];
    /** @var Model\Cart\Split\Interval[] */
    public $possibleIntervals = [];
    /** @var array */
    public $possibleDays = [];
    /** @var array */
    public $possiblePaymentMethodIds = [];
    /** @var array */
    public $groupedPossiblePointIds = [];
    /** @var Model\Cart\Split\Order\Point[] */
    public $possiblePoints = [];
    /** @var string|null */
    public $comment;

    /**
     * @param array $data
     */
    public function __construct($data = []) {
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
        $this->paymentMethodId = $data['payment_method_id'] ? (string)$data['payment_method_id'] : null;
        foreach ($data['possible_deliveries'] as $token) {
            $this->possibleDeliveryMethodTokens[] = (string)$token;
        }
        foreach ($data['possible_intervals'] as $item) {
            $this->possibleIntervals[] = new Interval($item);
        }
        foreach ($data['possible_days'] as $day) {
            $this->possibleDays[] = (string)$day;
        }
        foreach ((array)$data['possible_payment_methods'] as $id) { // FIXME: убрать приведение к массиву
            $this->possiblePaymentMethodIds[] = (string)$id;
        }
        foreach ($data['possible_points'] as $token => $ids) {
            foreach ($ids as $id) {
                $this->groupedPossiblePointIds[$token][] = (string)$id;
            }
        }
        foreach ($data['possible_point_data'] as $groupToken => $items) {
            foreach ($items as $item) {
                $possiblePoint = new Order\Point($item);
                $possiblePoint->groupToken = $groupToken;

                $this->possiblePoints[] = $possiblePoint;
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
            'possible_days'            => array_map(function($day) { return (string)$this->possibleDays; }, $this->possibleDays),
            'possible_payment_methods' => $this->possiblePaymentMethodIds,
            'possible_points'          => $this->groupedPossiblePointIds,
            'possible_point_data'      => $possiblePointsData,
            'comment'                  => $this->comment,
        ];
    }
}
