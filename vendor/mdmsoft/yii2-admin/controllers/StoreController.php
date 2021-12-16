<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-05-26 14:17
 */

namespace mdm\admin\controllers;
use backend\modules\v1\models\ApiSettings;
use backend\modules\v1\models\ApiTool;
use common\models\User;
use mdm\admin\models\StoreChild;
use mdm\admin\models\StoreChildCheck;
use yii\db\Exception;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use Yii;
use mdm\admin\models\searchs\StoreSearch;
use mdm\admin\models\Store;

class StoreController extends Controller
{
    public function actionIndex()
    {
        $model = new Store();
        if ($model->load(Yii::$app->request->post()) && $model->save())
        {
            $model = new Store(); //reset model
        }

        $searchModel = new StoreSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'model' => $model,
        ]);

    }

    public function actionCreate()
    {
        $model = new Store();
        $post = Yii::$app->request->post();
        if ($model->load(Yii::$app->request->post()) && $model->save())
        {
            //$model = new Store(); //reset model
            $storeChildModel = new StoreChild();
            $storeChildModel->store_id = $model->id;
            $storeChildModel->user_id = $post['Store']['username'];
            $storeChildModel->save();

            $check_username =  $post['Store']['check_username'] ? : [];
            foreach ($check_username as $v){
                $checkModel = new StoreChildCheck();
                $checkModel->store_id = $model->id;
                $checkModel->user_id = $v;
                $checkModel->save();
            }


            $this->redirect('index');
        }

        return $this->render('create', [
            'model' => $model
        ]);
    }

    public function actionUpdate($id)
    {
        $model = Store::findOne($id);
        $post = Yii::$app->request->post();
        if ($model->load(Yii::$app->request->post()) && $model->save())
        {
            //删除店铺原有归属人
            StoreChild::deleteAll(['store_id' => $model->id]);
            $storeChildModel = new StoreChild();
            $storeChildModel->store_id = $model->id;
            $storeChildModel->user_id = $post['Store']['username'];
            $storeChildModel->save();

            //删除店铺原有查看人
            StoreChildCheck::deleteAll(['store_id' => $model->id]);
            $check_username =  $post['Store']['check_username'] ? : [];
            foreach ($check_username as $v){
                $storeCheckModel = new StoreChildCheck();
                $storeCheckModel->store_id = $model->id;
                $storeCheckModel->user_id = $v;
                $storeCheckModel->save();
            }


            $this->redirect('index');
        }
        $user = StoreChild::find()
            ->join('INNER JOIN', 'user u' ,'u.id=user_id')
            ->where(['store_id' => $id, 'u.status' => User::STATUS_ACTIVE])->one();
        $check_user = StoreChildCheck::find()
            ->join('INNER JOIN', 'user u' ,'u.id=user_id')
            ->where(['store_id' => $id, 'u.status' => User::STATUS_ACTIVE])->all();

        $model->username = $user ? $user['user_id'] : 0;
        $model->check_username = ArrayHelper::getColumn($check_user, 'user_id');
        return $this->render('update', [
            'model' => $model
        ]);
    }

    public function actionDelete($id)
    {
        StoreChild::deleteAll(['store_id' => $id]);
        StoreChildCheck::deleteAll(['store_id' => $id]);
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Store::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionExport(){
        $sql = "SELECT s.store AS '账号',s.platform as '平台',u.username AS '归属人',GROUP_CONCAT(ucc.username) as '查看人',
						aa.username AS '主管',d.department as '小组', 
						CASE WHEN ifnull(pd.department,'')<>'' THEN pd.department ELSE d.department END AS '部门'
                FROM `auth_store` s 
                LEFT JOIN `auth_store_child` sc ON s.id=sc.store_id
                LEFT JOIN `user` u ON u.id=sc.user_id
                LEFT JOIN `auth_department_child` dc ON u.id=dc.user_id
                LEFT JOIN `auth_department` d ON d.id=dc.department_id
                LEFT JOIN `auth_department` pd ON pd.id=d.parent
                LEFT JOIN (
                    SELECT d.department,u.username,p.position  
                    FROM auth_department d
                    LEFT JOIN `auth_department_child` dc ON d.id=dc.department_id
                    LEFT JOIN `auth_department` pd ON pd.id=d.parent
                    LEFT JOIN `user` u ON u.id=dc.user_id
                    LEFT JOIN `auth_position_child` pc ON u.id=pc.user_id
                    LEFT JOIN `auth_position` p ON p.id=pc.position_id
                    WHERE pd.department='郑州分部' AND p.position='主管' OR  p.position='经理'
                ) aa ON aa.department = (CASE WHEN ifnull(pd.department,'')<>'' THEN pd.department ELSE d.department END)
                LEFT JOIN auth_store_child_check scc ON scc.store_id=s.id
                LEFT JOIN user ucc ON scc.user_id=ucc.id
                GROUP BY s.store,s.platform,u.username,aa.username,d.department, 
                        CASE WHEN ifnull(pd.department,'')<>'' THEN pd.department ELSE d.department END";
        /*$data = Store::find()->select("`store` as '店铺名称',`platform` as '平台',`username` as '归属人',`used` as '停用'")
            ->join('LEFT JOIN','auth_store_child sc','sc.store_id=auth_store.id')
            ->join('LEFT JOIN','user u','u.id=sc.user_id')
            ->asArray()->all();*/
        $data = Yii::$app->db->createCommand($sql)->queryAll();
        $title = "店铺（账号）归属人(查看人)详情";
        $titleList = ['账号','平台', '归属人', '查看人', '主管', '小组', '部门'];
        //var_dump($data);exit;
        ApiTool::exportExcel($title, $titleList, $data);
    }


    public function actionImport(){
        try {
            $file = $_FILES['file'];
            if (!$file) {
                throw new Exception('The upload file can not be empty!');
            }
            //判断文件后缀
            $extension = ApiSettings::get_extension($file['name']);
            if (!in_array($extension, ['.Xls', '.xls'])) return ['code' => 400, 'message' => "File format error,please upload files in 'Xls' format"];

            //文件上传
            $result = ApiSettings::file($file, 'storeUpdate');
            if (!$result) {
                throw new Exception('File upload failed!');
            } else {
                //获取上传excel文件的内容并保存
                $res = ApiSettings::saveStoreData($result);
//                if ($res !== true) return ['code' => 400, 'message' => $res];
                return $res;
            }
        } catch (\Exception $e) {
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
    }

}
