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
        $sku = isset($cond['sku']) ? $cond['sku'] : '';
        $suffix = isset($cond['suffix']) ? $cond['suffix'] : '';
        $creator = isset($cond['creator']) ? $cond['creator'] : '';
        $goodsName = isset($cond['goodsName']) ? $cond['goodsName'] : '';
        $createDateRange = isset($cond['createDateRange']) ? $cond['createDateRange'] : '';
        $completeDateRange1 = isset($cond['completeDateRange1']) ? $cond['completeDateRange1'] : '';
        $completeDateRange2 = isset($cond['completeDateRange2']) ? $cond['completeDateRange2'] : '';
        $status1 = isset($cond['status1']) ? $cond['status1'] : '';
        $status2 = isset($cond['status2']) ? $cond['status2'] : '';

        $sql = "SELECT g.goodsName,s.* FROM proCenter.`oa_smtImportToIbayLog` s
                LEFT JOIN proCenter.oa_goodsinfo g ON g.goodsCode=s.SKU where 1=1 ";
        if($sku)  $sql .= " AND g.goodsCode LIKE '%{$sku}%'";
        if($goodsName)  $sql .= " AND g.goodsName LIKE '%{$goodsName}%'";
        if($suffix)  $sql .= " AND s.ibaySuffix LIKE '%{$suffix}%'";
        if($creator)  $sql .= " AND s.creator LIKE '%{$creator}%'";
        if($createDateRange)  $sql .= " AND s.createDate BETWEEN '{$createDateRange[0]}' AND '" . $createDateRange[1].' 23:59:59' . "'";
        if($completeDateRange1)  $sql .= " AND s.completeDate1 BETWEEN '{$completeDateRange1[0]}' AND '" . $completeDateRange1[1].' 23:59:59' . "'";
        if($completeDateRange2)  $sql .= " AND s.completeDate2 BETWEEN '{$completeDateRange2[0]}' AND '" . $completeDateRange2[1].' 23:59:59' . "'";
        if($status1 OR $status1 === 0)  $sql .= " AND s.status1 = {$status1} ";
        if($status2 OR $status2 === 0)  $sql .= " AND s.status2 = {$status2} ";
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
