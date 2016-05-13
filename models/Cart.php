<?php
namespace pistol88\cart\models;

use pistol88\cart\interfaces\CartService;
use yii;

class Cart extends \yii\db\ActiveRecord implements CartService
{
    private $element = null;
    
    public function init()
    {
        $this->element = yii::$container->get('cartElement');
    }
    
    public function my()
    {
        $query = new tools\CartQuery(get_called_class());
        return $query->my();
    }
    
    public function put(\pistol88\cart\interfaces\ElementService $elementModel)
    {
        $elementModel->hash = self::_generateHash($elementModel->getModel(), $elementModel->getOptions());
        $elementModel->link('cart', $this->my());

        if ($elementModel->validate() && $elementModel->save()) {
            return $elementModel;
        } else {
            throw new \Exception(current($elementModel->getFirstErrors()));
        }
    }

    public function getElements()
    {
        return $this->hasMany($this->element, ['cart_id' => 'id']);
    }
    
    public function getElement(\pistol88\cart\interfaces\CartElement $model, $options = [])
    {
        return $this->getElements()->where(['hash' => $this->_generateHash($model, $options), 'item_id' => $model->getCartId()])->one();
    }
    
    public function getElementsByModel(\pistol88\cart\interfaces\CartElement $model)
    {
        return $this->getElements()->andWhere(['model' => get_class($model), 'item_id' => $model->getCartId()])->all();
    }
    
    public function getElementById($id)
    {
        return $this->getElements()->andWhere(['id' => $id])->one();
    }
    
    public function getCount()
    {
        return intval($this->getElements()->sum('count'));
    }
    
    public function getCost()
    {
        return $cost = $this->getElements()->sum('price*count');
    }
    
    public function truncate()
    {
        foreach($this->elements as $element) {
            $element->delete();
        }
        
        return $this;
    }

    public function rules()
    {
        return [
            [['created_time', 'user_id'], 'required', 'on' => 'create'],
            [['updated_time', 'created_time'], 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => yii::t('cart', 'ID'),
            'user_id' => yii::t('cart', 'User ID'),
            'created_time' => yii::t('cart', 'Created Time'),
            'updated_time' => yii::t('cart', 'Updated Time'),
        ];
    }
    
    public static function tableName()
    {
        return 'cart';
    }
    
    public function beforeDelete()
    {
        foreach ($this->elements as $elem) {
            $elem->delete();
        }
        
        return true;
    }
    
    private static function _generateHash(\pistol88\cart\interfaces\CartElement $model, $options = [])
    {  
        return md5(get_class($model).serialize($options));
    }
}
