<?php

namespace EnterModel\Cart {
    class Split {
        /** @var Split\DeliveryGroup[] */
        public $deliveryGroups = [];
        /** @var Split\DeliveryMethod[] */
        public $deliveryMethods = [];
        /** @var Split\Order[] */
        public $orders = [];
        /** @var Split\User|null */
        public $user;
        /** @var string|null */
        public $clientIp;

        public function __construct(array $data = []) {
            $this->deliveryGroups = array_map(function($data) { return new Split\DeliveryGroup($data); }, $data['delivery_groups']);
            $this->deliveryMethods = array_map(function($data) { return new Split\DeliveryMethod($data); }, $data['delivery_methods']);
            $this->orders = array_map(function($data) { return new Split\Order($data); }, $data['orders']);
            $this->user = $data['user_info'] ? new Split\User($data['user_info']) : null;
        }

        public function dump() {
            return [
                'delivery_groups'  => array_map(function(Split\DeliveryGroup $deliveryGroup) { return $deliveryGroup->dump(); }, $this->deliveryGroups),
                'delivery_methods' => array_map(function(Split\DeliveryMethod $deliveryMethod) { return $deliveryMethod->dump(); }, $this->deliveryMethods),
                'orders'           => array_map(function(Split\Order $product) { return $product->dump(); }, $this->orders),
                'user_info'        => $this->user ? $this->user->dump() : null,
            ];
        }
    }
}

namespace EnterModel\Cart\Split {
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
        /** @var string */
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
            $this->typeId = (string)$data['type_id'];
            $this->name = (string)$data['name'];
            $this->pointToken = (string)$data['point_token'];
            $this->groupId = (string)$data['group_id'];
            $this->description = $data['description'] ? (string)$data['description'] : null;
        }

        public function dump() {
            return [
                'token'       => $this->token,
                'type_id'     => $this->typeId,
                'name'        => $this->name,
                'point_token' => $this->pointToken,
                'group_id'    => $this->groupId,
                'description' => $this->description,
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

        public function __construct(array $data = []) {
            $this->name = (string)$data['block_name'];
            $this->seller = (bool)$data['seller'] ? new Order\Seller($data['seller']) : null;
            $this->products = array_map(function($data) { return new Order\Product($data); }, $data['products']);
            $this->discounts = array_map(function($data) { return new Order\Discount($data); }, $data['discounts']);
            $this->delivery = $data['delivery'] ? new Order\Delivery($data['delivery']) : null;
            $this->sum = $data['total_cost'];
            $this->paymentMethodId = $data['payment_method_id'] ? (string)$data['payment_method_id'] : null;
        }

        public function dump() {
            return [
                'block_name'        => $this->name,
                'seller'            => $this->seller ? $this->seller->dump() : null,
                'products'          => array_map(function(Order\Product $product) { return $product->dump(); }, $this->products),
                'discounts'         => array_map(function(Order\Discount $discount) { return $discount->dump(); }, $this->discounts),
                'delivery'          => $this->delivery ? $this->delivery->dump() : null,
                'total_cost'        => $this->sum,
                'payment_method_id' => $this->paymentMethodId ? (int)$this->paymentMethodId : null,
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

        public function __construct(array $data = []) {
            $this->methodToken = (string)$data['delivery_method_token'];
            $this->date = $data['date'] ? (int)$data['date'] : null;
            $this->price = $data['price'];
            $this->interval = $data['interval'] ? new Model\Cart\Split\Interval($data['interval']) : null;
            $this->point = $data['point'] ? new Delivery\Point($data['point']) : null;
            $this->hasUserAddress = (bool)$data['use_user_address'];
        }

        public function dump() {
            return [
                'delivery_method_token' => $this->methodToken,
                'date'                  => $this->date,
                'price'                 => $this->price,
                'interval'              => $this->interval ? $this->interval->dump() : null,
                'point'                 => $this->point ? $this->point->dump() : null,
                'use_user_address'      => $this->hasUserAddress,
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