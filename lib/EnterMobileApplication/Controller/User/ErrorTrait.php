<?php

namespace EnterMobileApplication\Controller\User;

trait ErrorTrait {
    /**
     * @param \Exception $e
     * @return array[]
     */
    public function getErrorsByException(\Exception $e) {
        $errors = [];

        switch ($e->getCode()) {
            case 684:
                $errors[] = ['code' => $e->getCode(), 'message' => 'Такой email уже занят', 'field' => 'email'];
                break;
            case 689:
                $errors[] = ['code' => $e->getCode(), 'message' => 'Неправильный email', 'field' => 'email'];
                break;
            case 686:
                $errors[] = ['code' => $e->getCode(), 'message' => 'Такой номер уже занят', 'field' => 'phone'];
                break;
            case 690:
                $errors[] = ['code' => $e->getCode(), 'message' => 'Неправильный телефон', 'field' => 'phone'];
                break;
            case 613:
                $errors[] = ['code' => $e->getCode(), 'message' => 'Неверный пароль', 'field' => 'password'];
                break;
            case 614:
                $errors[] = ['code' => $e->getCode(), 'message' => 'Пользователь не найден', 'field' => 'username'];
                break;
            case 609:
                $errors[] = ['code' => $e->getCode(), 'message' => 'Не удалось создать пользователя', 'field' => null];
                break;
            default:
                $errors[] = ['code' => $e->getCode(), 'message' => 'Произошла ошибка. Возможно неверно указаны логин или пароль', 'field' => null];
        }

        return $errors;
    }
}
