<?php
namespace pistol88\cart\widgets; 

use yii\helpers\Html;
use yii;

class TruncateButton extends \yii\base\Widget
{
    public $text = NULL;
    public $cssClass = 'btn btn-danger ';
    public $typeButton = false;
 
    public function init()
    {
        parent::init();

        \pistol88\cart\assets\WidgetAsset::register($this->getView());

        if ($this->text == NULL) {
            $this->text = yii::t('cart', 'Truncate');
        }
        
        return true;
    }

    public function run()
    {
        if ($this->typeButton) {
            return Html::button(Html::encode($this->text), ['class' => $this->cssClass, 'id' => 'truncate', 'style' => 'margin-left: 5px']);
        }
        return Html::a(Html::encode($this->text), ['/cart/default/truncate'], ['class' => $this->cssClass]);        
    }
}
