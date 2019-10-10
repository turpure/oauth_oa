<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-10-10 16:58
 */

namespace backend\modules\v1\controllers;

use backend\models\EbayProducts;


class ProductsEngineController extends AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiProductsEngine';

    public function actionRecommend()
    {
        return EbayProducts::find()->all();
    }

}