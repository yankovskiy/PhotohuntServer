<?php
/**
 * Copyright (C) 2014-2015  Artem Yankovskiy(artemyankovskiy@gmail.com)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
require_once 'db.php';
require_once 'auth.php';
require_once 'exceptions.php';

class ShopMgmt {
    private $mDb;

    /**
     * Выполняет подключение к базе данных
     * Генерирует исключение PDOException в случае ошибки подключения
     */
    public function connectToDb() {
        $this->mDb = new Database();
        $this->mDb->connect();
    }

    /**
     * Получает список предметов в магазине
     * @throws ShopException
     */
    public function getShop() {
        $auth = new Auth();
        if ($auth->authenticate($this->mDb)) {
            $items = $this->mDb->getShopItems();
            if (!isset($items)) {
                throw new ShopException("Проблема в работе магазина");
            }

            $user = $this->mDb->getUserByUserId($auth->getAuthenticatedUserId());
            $userItems = $this->mDb->getUserItems($user->id);
            $money = $user->money;
            $dc = $user->dc;

            if (isset($userItems)) {
                // если аватар уже куплен
                $isAvatarFound = false;
                foreach ($userItems as $userItem) {
                    if ($userItem->service_name == Item::AVATAR) {
                        $isAvatarFound = true;
                        break;
                    }
                }

                // убрать его из магазина
                $shopItems = array();
                if ($isAvatarFound) {
                    foreach ($items as $item) {
                        if ($item->service_name != Item::AVATAR) {
                            $shopItems[] = $item;
                        }
                    }
                } else {
                    $shopItems = $items;
                }
            } else {
                $shopItems = $items;
            }

            $data = array("money" => $money, "dc" => $dc, "shop_items" => $shopItems, "my_items" => $userItems);
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Получает список покупок пользователя
     */
    public function getMyItems() {
        $auth = new Auth();
        if ($auth->authenticate($this->mDb)) {
            $user = $this->mDb->getUserByUserId($auth->getAuthenticatedUserId());

            $items = $this->mDb->getUserItems($user->id);
            if (isset($items)) {
                echo json_encode($items, JSON_UNESCAPED_UNICODE);
            }
        }
    }

    /**
     * Производит покупку предмета в магазине
     * @param int $itemId id предмета в магазине
     * @throws ShopException
     */
    public function buyItem($itemId) {
        $auth = new Auth();
        if ($auth->authenticate($this->mDb)) {
            $user = $this->mDb->getUserByUserId($auth->getAuthenticatedUserId());
            $goods = $this->mDb->getShopItem($itemId);
            if (!isset($goods)) {
                throw new ShopException("Такого предмета в магазине нет");
            }
            
            $isHaveMoney = (($user->money >= $goods->price_money) && ($user->dc >= $goods->price_dc));
            if (!$isHaveMoney) {
                throw new ShopException("У вас недостаточно средств");
            }
            
            if ($goods->service_name == Item::AVATAR) {
                if ($this->isAvatarExists($user)) {
                    throw new ShopException("\"Аватар\" нельзя купить дважды");
                }
            }

            $this->mDb->userBuyItem($user, $goods);
        }
    }
    
    /**
     * Проверяет, куплен ли "аватар" у пользователя или нет
     * @param User $user пользователя для которого осуществить проверку купленного товара
     * @return boolean true если "аватар" уже куплен
     */
    private function isAvatarExists($user) {
        return $this->mDb->isGoodsExists($user->id, Item::AVATAR);
    }
}