<?php

namespace EnterModel\Cart {
    use EnterModel as Model;

    class Split {
        /** @var Model\Region */
        public $region;
        /** @var Split\DeliveryGroup[] */
        public $deliveryGroups = [];
        /** @var Split\DeliveryMethod[] */
        public $deliveryMethods = [];
        /** @var Split\PaymentMethod[] */
        public $paymentMethods = [];
        /** @var Split\PointGroup[] */
        public $pointGroups = [];
        /** @var Split\Order[] */
        public $orders = [];
        /** @var Split\User|null */
        public $user;
        /** @var string|null */
        public $clientIp;
        /** @var float */
        public $sum;

        public function __construct(array $data = []) {
            $this->deliveryGroups = array_map(function($data) { return new Split\DeliveryGroup($data); }, $data['delivery_groups']);
            $this->deliveryMethods = array_map(function($data) { return new Split\DeliveryMethod($data); }, $data['delivery_methods']);
            $this->paymentMethods = array_map(function($data) { return new Split\PaymentMethod($data); }, $data['payment_methods']);

            $this->pointGroups = [];
            foreach ($data['points'] as $pointGroupToken => $pointGroupItem) {
                $pointGroupItem['token'] = $pointGroupToken;
                $this->pointGroups[$pointGroupToken] = new Split\PointGroup($pointGroupItem);
            }

            $this->orders = array_map(function($data) { return new Split\Order($data); }, $data['orders']);
            $this->user = $data['user_info'] ? new Split\User($data['user_info']) : null;
            $this->sum = $data['total_cost'];
        }

        public function dump() {
            return [
                'delivery_groups'  => array_map(function(Split\DeliveryGroup $deliveryGroup) { return $deliveryGroup->dump(); }, $this->deliveryGroups),
                'delivery_methods' => array_map(function(Split\DeliveryMethod $deliveryMethod) { return $deliveryMethod->dump(); }, $this->deliveryMethods),
                'points'           => array_map(function(Split\PointGroup $pointGroup) { return $pointGroup->dump(); }, $this->pointGroups),
                'payment_methods'  => array_map(function(Split\PaymentMethod $paymentMethod) { return $paymentMethod->dump(); }, $this->paymentMethods),
                'orders'           => array_map(function(Split\Order $product) { return $product->dump(); }, $this->orders),
                'user_info'        => $this->user ? $this->user->dump() : null,
                'total_cost'       => $this->sum,
            ];
        }
    }
}

namespace EnterModel\Cart\Split {
    use EnterModel as Model;

    class Interval {
        /** @var string|null */
        public $from;
        /** @var string|null */
        public $to;

        public function __construct(array $data = []) {
            $this->from = $data['from'] ? (string)$data['from'] : null;
            $this->to = $data['to'] ? (string)$data['to'] : null;
        }

        public function dump() {
            return [
                'from' => $this->from,
                'to'   => $this->to,
            ];
        }
    }

    abstract class Point {
        /** @var string */
        public $id;
        /** @var string */
        public $name;
        /** @var string */
        public $address;
        /** @var string */
        public $regime;
        /** @var float */
        public $latitude;
        /** @var float */
        public $longitude;

        public function __construct(array $data = []) {
            $this->id = (string)$data['id'];
            $this->name = (string)$data['name'];
            $this->address = (string)$data['address'];
            $this->regime = (string)$data['regtime'];
            $this->latitude = (float)$data['latitude'];
            $this->longitude = (float)$data['longitude'];
        }

        public function dump() {
            return [
                'id'        => (int)$this->id, // ядерное
                'name'      => $this->name,
                'address'   => $this->address,
                'regtime'   => $this->regime,
                'latitude'  => $this->latitude,
                'longitude' => $this->longitude,
            ];
        }
    }

    class Subway {
        /** @var string */
        public $name;
        /** @var array */
        public $line;

        public function __construct(array $data = []) {
            $this->name = (string)$data['name'];
            $this->line = $data['line'] ? array_merge(['name' => null, 'color' => null], (array)$data['line']) : null;
        }

        public function dump() {
            return [
                'name' => $this->name,
                'line' => $this->line,
            ];
        }
    }

    class DeliveryGroup {
        /** @var string */
        public $id;
        /** @var string */
        public $name;

        public function __construct(array $data = []) {
            $this->id = (string)$data['id'];
            $this->name = (string)$data['name'];
        }

        public function dump() {
            return [
                'id'   => (int)$this->id, // ядерное
                'name' => $this->name,
            ];
        }
    }

    class DeliveryMethod {
        /** @var string */
        public $token;
        /** @var string|null */
        public $typeId;
        /** @var string */
        public $name;
        /** @var string */
        public $pointToken;
        /** @var string */
        public $groupId;
        /** @var string|null */
        public $description;

        public function __construct(array $data = []) {
            $this->token = (string)$data['token'];
            $this->typeId = $data['type_id'] ? (string)$data['type_id'] : null;
            $this->name = (string)$data['name'];
            $this->pointToken = $data['point_token'] ? (string)$data['point_token'] : null;
            $this->groupId = (string)$data['group_id'];
            $this->description = $data['description'] ? (string)$data['description'] : null;
        }

        public function dump() {
            return [
                'token'       => $this->token,
                'type_id'     => $this->typeId,
                'name'        => $this->name,
                'point_token' => (string)$this->pointToken, // ядерное
                'group_id'    => $this->groupId,
                'description' => $this->description,
            ];
        }
    }

    class PaymentMethod {
        /** @var string */
        public $id;
        /** @var string */
        public $name;
        /** @var string */
        public $description;

        public function __construct(array $data = []) {
            $this->id = (string)$data['id'];
            $this->name = (string)$data['name'];
            $this->description = $data['description'] ? (string)$data['description'] : null;
        }

        public function dump() {
            return [
                'id'          => $this->id,
                'name'        => $this->name,
                'description' => $this->description,
            ];
        }
    }

    class PointGroup {
        const TOKEN_SHOP = 'shops';
        const TOKEN_PICKPOINT = 'pickpoints';

        /** @var string */
        public $token;
        /** @var string */
        public $actionName;
        /** @var string */
        public $blockName;
        /** @var Model\Cart\Split\Point[] */
        public $points = [];

        public function __construct(array $data = []) {
            $this->token = (string)$data['token']; // ядро не высылает
            $this->actionName = (string)$data['action_name'];
            $this->blockName = (string)$data['block_name'];
            foreach ($data['list'] as $pointId => $pointItem) {
                switch ($this->token) {
                    case self::TOKEN_SHOP:
                        $this->points[$pointId] = new Model\Cart\Split\Point\Shop($pointItem);
                        break;
                    case self::TOKEN_PICKPOINT:
                        $this->points[$pointId] = new Model\Cart\Split\Point\Pickpoint($pointItem);
                        break;
                }
            }
        }

        public function dump() {
            $pointDump = [];
            switch ($this->token) {
                case self::TOKEN_SHOP:
                    $pointDump = array_map(function(Model\Cart\Split\Point\Shop $point) { return $point->dump(); }, $this->points);
                    break;
                case self::TOKEN_PICKPOINT:
                    $pointDump = array_map(function(Model\Cart\Split\Point\Pickpoint $point) { return $point->dump(); }, $this->points);
                    break;
            }

            return [
                'action_name' => $this->actionName,
                'block_name'  => $this->blockName,
                'list'        => $pointDump,
            ];
        }
    }

    class Order {
        /** @var string */
        public $name;
        /** @var Order\Seller|null */
        public $seller;
        /** @var Order\Product[] */
        public $products = [];
        /** @var Order\Discount[] */
        public $discounts = [];
        /** @var Order\Delivery */
        public $delivery;
        /** @var float */
        public $sum;
        /** @var string */
        public $paymentMethodId;
        /** @var string */
        public $comment;
        /** @var string[] */
        public $possibleDeliveryMethodTokens = [];
        /** @var Model\Cart\Split\Interval[] */
        public $possibleIntervals = [];
        /** @var int[] */
        public $possibleDays = [];
        /** @var Model\Cart\Split\PaymentMethod[] */
        public $possiblePaymentMethods = [];

        public function __construct(array $data = []) {
            $this->name = (string)$data['block_name'];
            $this->seller = (bool)$data['seller'] ? new Order\Seller($data['seller']) : null;
            $this->products = array_map(function($data) { return new Order\Product($data); }, $data['products']);
            $this->discounts = array_map(function($data) { return new Order\Discount($data); }, $data['discounts']);
            $this->delivery = $data['delivery'] ? new Order\Delivery($data['delivery']) : null;
            $this->sum = $data['total_cost'];
            $this->paymentMethodId = $data['payment_method_id'] ? (string)$data['payment_method_id'] : null;
            $this->comment = (string)$data['comment'];
            $this->possibleDeliveryMethodTokens = array_map(function($data) { return (string)$data; }, $data['possible_deliveries']);
            $this->possibleIntervals = array_map(function($data) { return new Model\Cart\Split\Interval($data); }, $data['possible_intervals']);
            $this->possibleDays = array_map(function($data) { return (int)$data; }, $data['possible_days']);
            $this->possiblePaymentMethods = array_map(function($data) { return (string)$data; }, $data['possible_payment_methods']);
        }

        public function dump() {
            return [
                'block_name'               => $this->name,
                'seller'                   => $this->seller ? $this->seller->dump() : null,
                'products'                 => array_map(function(Order\Product $product) { return $product->dump(); }, $this->products),
                'discounts'                => array_map(function(Order\Discount $discount) { return $discount->dump(); }, $this->discounts),
                'delivery'                 => $this->delivery ? $this->delivery->dump() : null,
                'total_cost'               => $this->sum,
                'payment_method_id'        => $this->paymentMethodId ? (int)$this->paymentMethodId : null,
                'comment'                  => $this->comment,
                'possible_deliveries'      => $this->possibleDeliveryMethodTokens,
                'possible_intervals'       => array_map(function(Model\Cart\Split\Interval $interval) { return $interval->dump(); }, $this->possibleIntervals),
                'possible_days'            => $this->possibleDays,
                'possible_payment_methods' => $this->possiblePaymentMethods,
            ];
        }
    }

    class User {
        /** @var string */
        public $phone;
        /** @var string */
        public $lastName;
        /** @var string */
        public $firstName;
        /** @var User\Address|null */
        public $address;
        /** @var string */
        public $email;

        public function __construct(array $data = []) {
            $this->phone = (string)$data['phone'];
            $this->lastName = (string)$data['last_name'];
            $this->firstName = (string)$data['first_name'];
            $this->address = $data['address'] ? new User\Address($data['address']) : null;
            $this->email = (string)$data['email'];
        }

        public function dump() {
            return [
                'phone'      => $this->phone,
                'last_name'  => $this->lastName,
                'first_name' => $this->firstName,
                'address'    => $this->address ? $this->address->dump() : null,
                'email'      => $this->email,
            ];
        }
    }
}

namespace EnterModel\Cart\Split\Point {
    use EnterModel as Model;

    class Shop extends Model\Cart\Split\Point {
        /** @var Model\Cart\Split\Subway[] */
        public $subway = [];

        public function __construct(array $data = []) {
            parent::__construct($data);

            $this->subway = array_map(function($data) { return new Model\Cart\Split\Subway($data); }, $data['subway']);
        }

        public function dump() {
            $dump = parent::dump();
            $dump['subway'] = array_map(function(Model\Cart\Split\Subway $subway) { return $subway->dump(); }, $this->subway);

            return $dump;
        }
    }

    class Pickpoint extends Model\Cart\Split\Point {
        // TODO

        public function __construct(array $data = []) {
            parent::__construct($data);
        }

        public function dump() {
            $dump = parent::dump();

            return $dump;
        }
    }
}

namespace EnterModel\Cart\Split\Order {
    use EnterModel as Model;

    class Seller {
        /** @var string|null */
        public $id;
        /** @var string */
        public $name;

        public function __construct(array $data = []) {
            $this->id = $data['id'] ? (string)$data['id'] : null;
            $this->name = (string)$data['name'];
        }

        public function dump() {
            return [
                'id'   => $this->id,
                'name' => $this->name,
            ];
        }
    }

    class Product {
        /** @var string */
        public $id;
        /** @var string */
        public $ui;
        /** @var string */
        public $name;
        /** @var string|null */
        public $namePrefix;
        /** @var string */
        public $webName;
        /** @var string */
        public $url;
        /** @var string|null */
        public $image;
        /** @var float */
        public $price;
        /** @var float */
        public $originalPrice;
        /** @var float */
        public $sum;
        /** @var int */
        public $quantity;
        /** @var int */
        public $stockQuantity;

        public function __construct(array $data = []) {
            $this->id = (string)$data['id'];
            $this->ui = (string)$data['ui'];
            $this->name = (string)$data['name'];
            $this->namePrefix = $data['prefix'] ? (string)$data['prefix'] : null;
            $this->webName = (string)$data['name_web'];
            $this->url = (string)$data['url'];
            $this->image = $data['image'] ? (string)$data['image'] : null;
            $this->price = $data['price'];
            $this->originalPrice = $data['original_price'];
            $this->sum = $data['sum'];
            $this->quantity = (int)$data['quantity'];
            $this->stockQuantity = (int)$data['stock'];
        }

        public function dump() {
            return [
                'id'             => $this->id,
                'ui'             => $this->ui,
                'name'           => $this->name,
                'prefix'         => $this->namePrefix,
                'name_web'       => $this->webName,
                'url'            => $this->url,
                'image'          => $this->image,
                'price'          => $this->price,
                'original_price' => $this->originalPrice,
                'sum'            => $this->sum,
                'quantity'       => $this->quantity,
                'stock'          => $this->stockQuantity,
            ];
        }
    }

    class Discount {
        // TODO

        public function __construct(array $data = []) {

        }

        public function dump() {
            return [

            ];
        }
    }

    class Delivery {
        /** @var string */
        public $methodToken;
        /** @var int|null */
        public $date;
        /** @var float */
        public $price;
        /** @var Model\Cart\Split\Interval */
        public $interval;
        /** @var Delivery\Point|null */
        public $point;
        /** @var bool */
        public $hasUserAddress;
        /** @var string|null */
        public $typeId;
        /** @var string|null */
        public $modeId;

        public function __construct(array $data = []) {
            $this->methodToken = (string)$data['delivery_method_token'];
            $this->date = $data['date'] ? (int)$data['date'] : null;
            $this->price = $data['price'];
            $this->interval = $data['interval'] ? new Model\Cart\Split\Interval($data['interval']) : null;
            $this->point = $data['point'] ? new Delivery\Point($data['point']) : null;
            $this->hasUserAddress = (bool)$data['use_user_address'];
            $this->typeId = $data['type_id'] ? (string)$data['type_id'] : null;
            $this->modeId = $data['mode_id'] ? (string)$data['mode_id'] : null;
        }

        public function dump() {
            return [
                'delivery_method_token' => $this->methodToken,
                'date'                  => $this->date,
                'price'                 => $this->price,
                'interval'              => $this->interval ? $this->interval->dump() : null,
                'point'                 => $this->point ? $this->point->dump() : null,
                'use_user_address'      => $this->hasUserAddress,
                'type_id'               => $this->typeId,
                'mode_id'               => $this->modeId,
            ];
        }
    }
}

namespace EnterModel\Cart\Split\Order\Delivery {
    class Point {
        /** @var string */
        public $token;
        /** @var string */
        public $id;

        public function __construct(array $data = []) {
            $this->token = (string)$data['token'];
            $this->id = (string)$data['id'];
        }

        public function dump() {
            return [
                'token' => $this->token,
                'id'    => $this->id ? (int)$this->id : null, // ядерное
            ];
        }
    }
}

namespace EnterModel\Cart\Split\User {
    class Address {
        /** @var string */
        public $street;
        /** @var string */
        public $building;
        /** @var string */
        public $number;
        /** @var string */
        public $apartment;
        /** @var string */
        public $floor;
        /** @var string */
        public $subwayName;

        public function __construct(array $data = []) {
            $this->street = $data['street'];
            $this->building = $data['building'];
            $this->number = $data['number'];
            $this->apartment = $data['apartment'];
            $this->floor = $data['floor'];
            $this->subwayName = $data['metro_station'];
        }

        public function dump() {
            return [
                'street'        => $this->street,
                'building'      => $this->building,
                'number'        => $this->number,
                'apartment'     => $this->apartment,
                'floor'         => $this->floor,
                'metro_station' => $this->subwayName,
            ];
        }
    }
}