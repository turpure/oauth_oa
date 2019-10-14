<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-10-10 16:58
 */

namespace backend\modules\v1\controllers;

use backend\models\EbayProducts;
use backend\models\WishProducts;




class ProductsEngineController extends AdminController
{

    public $modelClass = 'backend\modules\v1\models\ApiProductsEngine';

    /**
     * @brief recommend  products
     * @return mixed
     */
    public function actionRecommend()
    {
        try {
            $plat = \Yii::$app->request->get('plat');
            if ($plat === 'ebay') {
                $station = \Yii::$app->request->get('station','US');
                return EbayProducts::find()->where(['station' => $station])->all();
            }
            if ($plat === 'wish') {
                return WishProducts::find()->all();
            }
        }
        catch (\Exception $why) {
            return ['code' => 401, 'message' => $why->getMessage()];
        }

    }

}
