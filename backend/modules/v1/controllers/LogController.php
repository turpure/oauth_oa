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


use backend\models\OaShopifyImportToBackstageLog;
use backend\modules\v1\models\ApiUser;
use Yii;
use yii\data\ArrayDataProvider;
use yii\db\Exception;

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
    public function actionSmtExportLog()
    {

        $username = Yii::$app->user->identity->username;
        $userArr = ApiUser::getUserList($username);
        $userList = implode("','", $userArr);
//        var_dump($userList);exit;

        $cond = Yii::$app->request->post('condition');
        $pageSize = isset($cond['pageSize']) ? $cond['pageSize'] : 20;
        $goodsCode = isset($cond['SKU']) ? $cond['SKU'] : '';
        $mubanId = isset($cond['mubanId']) ? $cond['mubanId'] : '';
        $ibaySuffix = isset($cond['ibaySuffix']) ? $cond['ibaySuffix'] : '';
        $creator = isset($cond['creator']) ? $cond['creator'] : '';
        $goodsName = isset($cond['goodsName']) ? $cond['goodsName'] : '';
        $createDate = isset($cond['createDate']) ? $cond['createDate'] : '';
        $completeDate1 = isset($cond['completeDate1']) ? $cond['completeDate1'] : '';
        $completeDate2 = isset($cond['completeDate2']) ? $cond['completeDate2'] : '';
        $status1 = isset($cond['status1']) ? $cond['status1'] : '';
        $status2 = isset($cond['status2']) ? $cond['status2'] : '';

        $sql = "SELECT g.goodsName,s.* FROM proCenter.`oa_smtImportToIbayLog` s
                LEFT JOIN proCenter.oa_goodsinfo g ON g.goodsCode=s.SKU where 1=1 ";
        if ($goodsCode) $sql .= " AND g.goodsCode LIKE '%{$goodsCode}%'";
        if ($goodsName) $sql .= " AND g.goodsName LIKE '%{$goodsName}%'";
        if ($ibaySuffix) $sql .= " AND s.ibaySuffix LIKE '%{$ibaySuffix}%'";
        if ($creator) $sql .= " AND s.creator LIKE '%{$creator}%'";
        if ($mubanId) $sql .= " AND s.mubanId LIKE '%{$mubanId}%'";
        if ($createDate) $sql .= " AND s.createDate BETWEEN '{$createDate[0]}' AND '" . $createDate[1] . ' 23:59:59' . "'";
        if ($completeDate1) $sql .= " AND s.completeDate1 BETWEEN '{$completeDate1[0]}' AND '" . $completeDate1[1] . ' 23:59:59' . "'";
        if ($completeDate2) $sql .= " AND s.completeDate2 BETWEEN '{$completeDate2[0]}' AND '" . $completeDate2[1] . ' 23:59:59' . "'";
        if ($status1 OR $status1 == '0') $sql .= " AND s.status1 = {$status1} ";
        if ($status2 OR $status2 == '0') $sql .= " AND s.status2 = {$status2} ";

        $sql .= " AND s.creator IN ('{$userList}') ORDER BY s.createDate DESC ";
        $ret = Yii::$app->db->createCommand($sql)->queryAll();
        $provider = new ArrayDataProvider([
            'allModels' => $ret,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);

        return $provider;
    }

    /**
     * Date: 2021-01-28 9:15
     * Author: henry
     * @return ArrayDataProvider
     * @throws \yii\db\Exception
     */
    public function actionShopifyExportLog()
    {
        $cond = Yii::$app->request->post('condition');
        $pageSize = isset($cond['pageSize']) ? $cond['pageSize'] : 20;
        $sku = isset($cond['sku']) ? $cond['sku'] : '';
        $suffix = isset($cond['suffix']) ? $cond['suffix'] : '';
        $product_id = isset($cond['product_id']) ? $cond['product_id'] : '';
        $creator = isset($cond['creator']) ? $cond['creator'] : '';
        $createDate = isset($cond['createDate']) ? $cond['createDate'] : [];
        $updateDate = isset($cond['updateDate']) ? $cond['updateDate'] : [];
        $content = isset($cond['content']) ? $cond['content'] : '';

        $query = OaShopifyImportToBackstageLog::find()
            ->andFilterWhere(['like', 'sku', $sku])
            ->andFilterWhere(['like', 'suffix', $suffix])
            ->andFilterWhere(['like', 'product_id', $product_id])
            ->andFilterWhere(['like', 'creator', $creator]);

            //->andFilterWhere(['between', 'updateDate', $updateDate[0], $updateDate[1]]);
        if ($content == '是') {
            $query->andFilterWhere(['content' => 'success']);
        }
        if ($content == '否') {
            $query->andFilterWhere(['<>', 'content', 'success']);
        }
        if($createDate){
            $query->andFilterWhere(['between', 'createDate', $createDate[0], $createDate[1]]);
        }
        if($updateDate){
            $query->andFilterWhere(['between', 'updateDate', $updateDate[0], $updateDate[1]]);
        }
        $data = $query->orderBy('createDate DESC')->asArray()->all();

        $provider = new ArrayDataProvider([
            'allModels' => $data,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);

        return $provider;
    }

    /**
     * Date: 2021-01-28 9:15
     * Author: henry
     * @return array | bool
     * @throws \yii\db\Exception
     */
    public function actionShopifyExportLogDelete()
    {
        $con = Yii::$app->request->post('condition', []);
        $ids = isset($con['ids']) ? $con['ids'] : [];
        if(!is_array($ids)){
            $ids = [$ids];
        }
        try{
            OaShopifyImportToBackstageLog::deleteAll(['id' => $ids]);
            return true;
        }catch(Exception $e){
            return ['code' => 400, 'message' => $e->getMessage()];
        }


    }

}



