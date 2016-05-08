<?php
namespace pistol88\cart\widgets; 

use yii\helpers\Url;
use yii\helpers\Html;
use yii;

class ChangeOptions extends \yii\base\Widget
{
    public $model = NULL;
    public $type = 'select';
    public $cssClass = '';
    public $defaultValues = [];
    
    public function init()
    {
        parent::init();
        
        \pistol88\cart\assets\WidgetAsset::register($this->getView());
        
        return true;
    }

    public function run()
    {
        if($this->model instanceof \pistol88\cart\interfaces\CartElement) {
            $optionsList = $this->model->getCartOptions();
            $changerCssClass = 'pistol88-option-values-before';
        }
        else {
            $optionsList = $this->model->cartElementModel->getCartOptions();
            $this->defaultValues = $this->model->getOptions();
            $changerCssClass = 'pistol88-option-values';
        }

        if(!empty($optionsList)) {
            $i = 1;
            foreach($optionsList as $option => $values) {
                if(!is_array($values)) {
                    $values = [];
                }
                $cssClass = "{$changerCssClass} pistol88-cart-option{$this->model->getCartId()} ";
                if($this->type == 'select') {
                    array_unshift($values, $option);
                    $list = Html::dropDownList('cart_options' . $this->model->getCartId().'-'.$i,
                        $this->_defaultValue($option),
                        $values,
                        ['data-href' => Url::toRoute("/cart/element/update"), 'data-name' => Html::encode($option), 'data-id' => $this->model->getCartId(), 'class' => "form-control $cssClass"]
                    );
                } else {
                    array_unshift($values, yii::t('cart', 'Empty'));
                    $list = Html::tag('div', Html::tag('strong', $option), ['class' => 'pistol88-option-heading']);
                    $list .= Html::radioList('cart_options' . $this->model->getCartId().'-'.$i,
                        $this->_defaultValue($option),
                        $values,
                        ['itemOptions' => ['data-href' => Url::toRoute("/cart/element/update"), 'data-name' => $option, 'data-id' => $this->model->getCartId(), 'class' => $cssClass]]
                    );
                }
                $options[] = Html::tag('form', $list, ['class' => "pistol88-option"]);
                $i++;
            }
        }
        else {
            return null;
        }
        
        return Html::tag('div', implode('', $options), ['class' => 'pistol88-change-options ' . $this->cssClass]);
    }
    
    private function _defaultValue($option)
    {
        if(isset($this->defaultValues[$option])) {
            return $this->defaultValues[$option];
        }
        
        return false;
    }

}
