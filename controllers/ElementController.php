<?php
namespace pistol88\cart\controllers;

use yii\helpers\Json;
use yii\filters\VerbFilter;
use common\models\Ware;
use yii;

class ElementController extends \yii\web\Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'create' => ['post'],
                    'delete' => ['post'],
                ],
            ],
        ];
    }
    
    public function actionDelete()
    {
        $json = ['result' => 'undefind', 'error' => false];
        $elementId = yii::$app->request->post('elementId');

        $cart = yii::$app->cart;
        
        $elementModel = $cart->getElementById($elementId);
        
        if($elementModel->delete()) {
            $json['result'] = 'success';
        }
        else {
            $json['result'] = 'fail';
        }

        return $this->_cartJson($json);
    }
	
    public function actionCreate()
    {
        $json = ['result' => 'undefind', 'error' => false];

        $cart = yii::$app->cart;

        $postData = yii::$app->request->post();

        $model = $postData['CartElement']['model'];
        if($model) {
            $productModel = new $model();
            $productModel = $productModel::findOne($postData['CartElement']['item_id']);

            if ($productModel->wareLimit > 0) {
                $options = [];
                if(isset($postData['CartElement']['options'])) {
                    $options = $postData['CartElement']['options'];
                }
    
                if($postData['CartElement']['price'] && $postData['CartElement']['price'] != 'false') {
                    $elementModel = $cart->putWithPrice($productModel, $postData['CartElement']['price'], $postData['CartElement']['count'], $options);
                } else {
                    $elementModel = $cart->put($productModel, $postData['CartElement']['count'], $options);
                }
    
                $json['elementId'] = $elementModel->getId();
                $json['result'] = 'success';
            } else {
                $json['result'] = 'fail';
                $json['error'] = 'no product';
                Yii::$app->session->setFlash('error', $productModel->name . Yii::t('cart', ' not available'));
            }            
        } else {
            $json['result'] = 'fail';
            $json['error'] = 'empty model';
        }

        return $this->_cartJson($json);
    }

    public function actionUpdate()
    {
        $json = ['result' => 'undefind', 'error' => false];

        $cart = yii::$app->cart;
        
        $postData = yii::$app->request->post();

        $elementModel = $cart->getElementById($postData['CartElement']['id']);
        
        if (!$elementModel) {
            return $this->_cartJson($json);
        }
        
        try {
            $wareModel = $elementModel->getModel();
        
            if(isset($postData['CartElement']['count'])) {
                if ($wareModel->getWareLimit() == null) {
                    $elementModel->delete();
                    Yii::$app->session->setFlash('error', $wareModel->name . ' ' . Yii::t('cart', 'not available'));
                } elseif ($wareModel->getQuantityExceeded($postData['CartElement']['count'])) {
                    $elementModel->setCount($wareModel->getWareLimit(), true);
                    Yii::$app->session->setFlash('warning', Yii::t('cart', 'The limit of the quantity of the order of the given goods equal to ') . $wareModel->getWareLimit());
                } else {
                    $elementModel->setCount($postData['CartElement']['count'], true);
                }          
            }
            
            if(isset($postData['CartElement']['options'])) {
                $elementModel->setOptions($postData['CartElement']['options'], true);
            }
            
            $json['elementId'] = $elementModel->getId();
            $json['result'] = 'success';
    
            return $this->_cartJson($json);
        } catch (\pistol88\cart\exceptions\CartChangeCountException $e) {
            Yii::$app->session->setFlash('warning', Yii::t('error', $e->getMessage()));
        } catch (\pistol88\cart\exceptions\CartDeleteItemException $e) {
            Yii::$app->session->setFlash('error', Yii::t('error', $e->getMessage()));
        }
        return $this->_cartJson($json);
    }

    private function _cartJson($json)
    {
        if ($cartModel = yii::$app->cart) {
            if(!$elementsListWidgetParams = yii::$app->request->post('elementsListWidgetParams')) {
                $elementsListWidgetParams = [];
            }
            
            $json['elementsHTML'] = \pistol88\cart\widgets\ElementsList::widget($elementsListWidgetParams);
            $json['count'] = $cartModel->getCount();
            $json['clear_price'] = $cartModel->getCount(false);
            $json['price'] = $cartModel->getCostFormatted();
        } else {
            $json['count'] = 0;
            $json['price'] = 0;
            $json['clear_price'] = 0;
        }
        return Json::encode($json);
    }

}
