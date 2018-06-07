<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-05-26 14:17
 */

namespace mdm\admin\controllers;
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
        if ($model->load(Yii::$app->request->post()) && $model->save())
        {
            $model = new Store(); //reset model
            $this->redirect('index');
        }

        return $this->render('create', [
            'model' => $model
        ]);
    }

    public function actionUpdate($id)
    {
        $model = Store::findOne($id);
        if ($model->load(Yii::$app->request->post()) && $model->save())
        {
            $this->redirect('index');
        }

        return $this->render('update', [
            'model' => $model
        ]);
    }

    public function actionDelete($id)
    {
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

}