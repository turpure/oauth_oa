<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-05-26 14:17
 */

namespace mdm\admin\controllers;
use backend\modules\v1\models\ApiTool;
use common\models\User;
use mdm\admin\models\StoreChild;
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
            $this->redirect('index');
        }
        $user = StoreChild::find()
            ->join('INNER JOIN', 'user u' ,'u.id=user_id')
            ->where(['store_id' => $id, 'u.status' => User::STATUS_ACTIVE])->one();

        $model->username = $user ? $user['user_id'] : 0;
        return $this->render('update', [
            'model' => $model
        ]);
    }

    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        StoreChild::deleteAll(['store_id' => $id]);

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
        $data = Store::find()->select("`store` as '店铺名称',`platform` as '平台',`username` as '归属人',`used` as '停用'")
            ->join('LEFT JOIN','auth_store_child sc','sc.store_id=auth_store.id')
            ->join('LEFT JOIN','user u','u.id=sc.user_id')
            ->asArray()->all();
        $title = "店铺（账号）归属人详情";
        $titleList = ['店铺名称','平台', '归属人', '停用'];
        //$data = array_map('get_object_vars',$data);
        ApiTool::exportExcel($title, $titleList, $data);
        //var_dump($data);exit;
    }

}