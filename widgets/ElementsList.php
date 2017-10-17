<?php
namespace pistol88\cart\widgets;

use pistol88\cart\widgets\DeleteButton;
use pistol88\cart\widgets\TruncateButton;
use pistol88\cart\widgets\ChangeCount;
use pistol88\cart\widgets\CartInformer;
use yii\helpers\Html;
use yii\helpers\Url;
use yii;

class ElementsList extends \yii\base\Widget
{
    const TYPE_DROPDOWN = 'dropdown';
    const TYPE_FULL = 'full';
    
    public $offerUrl = NULL;
    public $textButton = NULL;
    public $type = NULL;
    public $model = NULL;
    public $cart = NULL;
    public $showTotal = false;
    public $showOptions = true;
    public $showOffer = false;
    public $showTruncate = false;
    public $truncateCss = 'btn btn-danger ';
    public $currency = null;
    public $otherFields = [];
    public $currencyPosition = null;
    public $showCountArrows = true;
    public $cartHeader = null;
    public $typeButton = false;
    //public $columns = 4;
    
    public function init()
    {
        $paramsArr = [
            'offerUrl' => $this->offerUrl,
            'textButton' => $this->textButton,
            'type' => $this->type,
            //'columns' => $this->columns,
            'model' => $this->model,
            'showTotal' => $this->showTotal,
            'showOptions' => $this->showOptions,
            'showOffer' => $this->showOffer,
            'showTruncate' => $this->showTruncate,
            'currency' => $this->currency,
            'otherFields' => $this->otherFields,
            'currencyPosition' => $this->currencyPosition,
            'showCountArrows' => $this->showCountArrows,
            'typeButton' => $this->typeButton,
        ];
        
        foreach($paramsArr as $key => $value) {
            if($value === 'false') {
                $this->$key = false;
            }
        }

        $this->getView()->registerJs("pistol88.cart.elementsListWidgetParams = ".json_encode($paramsArr));
        
        if ($this->type == NULL) {
            $this->type = self::TYPE_FULL;
        }

        if ($this->offerUrl == NULL) {
            $this->offerUrl = Url::toRoute(['/cart/default/index']);
        }

        if ($this->cart == NULL) {
            $this->cart = yii::$app->cart;
        }

        if ($this->textButton == NULL) {
            $this->textButton = yii::t('cart', 'Cart (<span class="pistol88-cart-price">{p}</span>)', ['c' => $this->cart->getCount(), 'p' => $this->cart->getCostFormatted()]);
        }
        
        if ($this->currency == NULL) {
            $this->currency = yii::$app->cart->currency;
        }
        
        if ($this->currencyPosition == NULL) {
            $this->currencyPosition = yii::$app->cart->currencyPosition;
        }
        
        if ($this->cartHeader == NULL) {
            $columns = [];
            $columns[] = Html::tag('div', yii::t('cart', 'Ware'), ['class' => 'col-lg-7 col-md-7 col-xs-7']);
            $columns[] = Html::tag('div', yii::t('cart', 'Price'), ['class' => 'col-lg-1 col-md-1 col-xs-1 basket']);
            $columns[] = Html::tag('div', yii::t('cart', 'Count'), ['class' => 'col-lg-2 col-md-2 col-xs-2 text-center basket']);
            $columns[] = Html::tag('div', yii::t('cart', 'Cost'), ['class' => 'col-lg-1 col-md-1 col-xs-1 basket']);
            $columns[] = Html::tag('div', '', ['class' => 'shop-cart-delete col-lg-1 col-md-1 col-xs-1 basket']);        
            $this->cartHeader = html::tag('div', implode('', $columns), ['class' => ' row']);
        }
   
        \pistol88\cart\assets\WidgetAsset::register($this->getView());

        return parent::init();
    }

    public function run()
    {
        $elements = $this->cart->elements;

        if (empty($elements)) {
            $cart = Html::tag('h5', yii::t('cart', 'Your cart empty'), ['class' => 'pistol88-cart pistol88-empty-cart alert alert-danger']);
        } else {
            $cart = Html::tag('ul', Html::tag('li', $this->cartHeader, ['class' => 'pistol88-cart-row ']), ['class' => 'pistol88-cart-list bg-primary']);
        	$cart .= Html::ul($elements, ['item' => function($item, $index) {
                return $this->_row($item);
            }, 'class' => 'pistol88-cart-list']);
		}
		
        if (!empty($elements)) {
            $bottomPanel = '';
            
            if ($this->showTotal) {
                $bottomPanel .= Html::tag('div', Yii::t('cart', 'Total') . ': ' . yii::$app->cart->costFormatted, ['class' => 'pistol88-cart-total-row text-right text-info']);
            }
            
            if($this->offerUrl && $this->showOffer) {
                if ($this->typeButton) {
                    $bottomPanel .= Html::button(yii::t('cart', 'Offer'), ['class' => 'btn btn-success', 'id' => 'checkout', 'data-url' => $this->offerUrl]);
                } else {
                    $bottomPanel .= Html::a(yii::t('cart', 'Offer'), $this->offerUrl, ['class' => 'btn btn-primary']);
                }
            }
            
            if($this->showTruncate) {
                $bottomPanel .= TruncateButton::widget([
                    'typeButton' => $this->typeButton,
                    'cssClass' => $this->truncateCss
                ]);
            }
            
            $cart .= Html::tag('div', $bottomPanel, ['class' => 'pistol88-cart-bottom-panel']);
        }
        
        $cart = Html::tag('div', $cart, ['class' => 'pistol88-cart']);

        if ($this->type == self::TYPE_DROPDOWN) {
            $button = Html::button($this->textButton.Html::tag('span', '', ["class" => "caret"]), ['class' => 'btn dropdown-toggle', 'id' => 'pistol88-cart-drop', 'type' => "button", 'data-toggle' => "dropdown", 'aria-haspopup' => 'true', 'aria-expanded' => "false"]);
            $list = Html::tag('div', $cart, ['class' => 'dropdown-menu', 'aria-labelledby' => 'pistol88-cart-drop']);
            $cart = Html::tag('div', $button.$list, ['class' => 'pistol88-cart-dropdown dropdown']);
        }
        return Html::tag('div', $cart, ['class' => 'pistol88-cart-block']);
    }

    private function _row($item)
    {
        if (is_string($item)) {
            return html::tag('li', $item);
        }
        
        $columns = [];

        $product = $item->getModel();
        
        $allOptions = $product->getCartOptions();
        
        $cartElName = $product->getCartName();
        
        $cartPrice = $product->getCartPrice();

        if($this->showOptions && $item->getOptions()) {
            $options = '';
            foreach($item->getOptions() as $optionId => $valueId) {
                if($optionData = $allOptions[$optionId]) {
                    $option = $optionData['name'];

                    $value = $optionData['variants'][$valueId];

                    $options .= Html::tag('div', Html::tag('strong', $option) . ':' . $value);
                }
            }
            
            $cartElName .= Html::tag('div', $options, ['class' => 'pistol88-cart-show-options']);
        }

        if(!empty($this->otherFields)) {
            foreach($this->otherFields as $fieldName => $field) {
                $cartElName .= Html::tag('p', Html::tag('small', $fieldName.': '.$product->$field));
            }
        }

        $columns[] = Html::tag('div', $cartElName, ['class' => 'col-lg-7 col-md-7 col-xs-7']);
        $columns[] = Html::tag('div', $this->_getCostFormatted($cartPrice), ['class' => 'col-lg-1 col-md-1 col-xs-1 basket']);
        $columns[] = Html::tag('div', ChangeCount::widget(['model' => $item, 'showArrows' => $this->showCountArrows]), ['class' => 'col-lg-2 col-md-2 col-xs-2 text-center basket']);
        $columns[] = Html::tag('div', $this->_getCostFormatted($item->getCost(false)), ['class' => 'col-lg-1 col-md-1 col-xs-1 basket']);
        $columns[] = Html::tag('div', DeleteButton::widget(['model' => $item, 'lineSelector' => 'pistol88-cart-row ', 'cssClass' => 'delete']), ['class' => 'shop-cart-delete col-lg-1 col-md-1 col-xs-1 basket text-center']);
        
        $return = html::tag('div', implode('', $columns), ['class' => ' row']);
        
        return Html::tag('li', $return, ['class' => 'pistol88-cart-row ']);
    }
    
    private function _getCostFormatted($cost)
    {
        $priceFormat = yii::$app->cart->priceFormat;   
        $costFormatted = number_format($cost, $priceFormat[0], $priceFormat[1], $priceFormat[2]);
        if ($this->currencyPosition == 'after') {
            return "$costFormatted{$this->currency}";
        } else {
            return "{$this->currency}$costFormatted";
        }
    }
}
