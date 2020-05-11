<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020-05-11
 * Time: 8:42
 * Author: henry
 */
/**
 * @name LogController.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2020-05-11 8:42
 */


namespace backend\modules\v1\controllers;


use Yii;
use yii\data\ArrayDataProvider;

class LogController extends AdminController
{
    public $modelClass = 'backend\models\AuthPositionMenu';

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];


    /**
     * Date: 2020-05-11 9:15
     * Author: henry
     * @return ArrayDataProvider
     * @throws \yii\db\Exception
     */
    public function actionSmtExportLog(){
        $cond = Yii::$app->request->post('condition');
        $pageSize = isset($cond['pageSize']) ? $cond['pageSize'] : 20;
        $sql = "SELECT g.goodsName,s.* FROM proCenter.`oa_smtImportToIbayLog` s
                LEFT JOIN proCenter.oa_goodsinfo g ON g.goodsCode=s.SKU;";
        $ret = Yii::$app->db->createCommand($sql)->queryAll();
        $provider = new ArrayDataProvider([
            'allModels' => $ret,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);

        return $provider;
    }




}
