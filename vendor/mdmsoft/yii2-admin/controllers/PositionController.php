<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-05-26 14:17
 */

namespace mdm\admin\controllers;
use yii\web\Controller;
use Yii;
use mdm\admin\models\searchs\PositionSearch;
use mdm\admin\models\Position;
use mdm\admin\models\form\UpdatePosition;

class PositionController extends Controller
{
    public function actionIndex()
    {
        $model = new Position();
        if ($model->load(Yii::$app->request->post()) && $model->save())
        {
            $model = new Position(); //reset model
        }

        $searchModel = new PositionSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'model' => $model,
        ]);

    }

    public function actionUpdate($id)
    {
        $model = new UpdatePosition($id);

        if ($model->load(Yii::$app->getRequest()->post())) {
            if($model->save()) {
                $this->redirect('index');
            }
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionCreate()
    {
        $model = new Position();
        if ($model->load(Yii::$app->request->post()) && $model->save())
        {
            $model = new Position(); //reset model
            $this->redirect('index');
        }

        return $this->render('create', [
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
        if (($model = Position::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

}