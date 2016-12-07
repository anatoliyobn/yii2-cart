<?php
namespace pistol88\cart\models\tools;
use pistol88\cart\models\Cart;

use yii\web\Session;
use yii;

class CartQuery extends \yii\db\ActiveQuery
{
    public function my()
    {
        $session = yii::$app->session;
        
        if (!$userId = yii::$app->user->id) { // юзер не авторизован
            if (!$userId = $session->get('guestHash')) { // у юзера нет сессии -> создать сессию
                $userId = md5(time() . '-' . yii::$app->request->userIP . Yii::$app->request->absoluteUrl);
                $session->set('guestHash', $userId);
            }
        } elseif ($session->get('guestHash')) { // юзер авторизовался и есть сессия для корзины
            $exist_cart = $this->findCart($userId); // существующая корзина для авторизованного пользоваятеля
            $new_cart = $this->findCart($session->get('guestHash')); // корзина из сессии            
            if ($exist_cart && $new_cart->count > 0) { // если для этого юзера уже существовала корзина, а новая корзина не пустая, то удаляем старую и меняем у новой ID юзера
                $exist_cart->delete();
                $new_cart->user_id = $userId;
                $new_cart->update();
            } else { // удаляем текущую пустую
                $new_cart->delete();
            }           
            $session->remove('guestHash'); // удалить сессию            
        }

        $one = isset($new_cart) ? $new_cart : $this->andWhere(['user_id' => $userId])->one();
        
        if (!$one) {
            $one = $this->createCart($userId);
        }
        
        return $one;
    }

    static function findCart($userId) {
        return Cart::find()->where(['user_id' => $userId])->one();
    }
    
    static function createCart($userId) {
        $cart= new Cart;
        $cart->created_time = time();
        $cart->updated_time = time();
        $cart->user_id = $userId;
        $cart->save();
        return $cart;
    }
}