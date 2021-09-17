<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-05-26 14:17
 */

namespace mdm\admin\controllers;
use mdm\admin\models\Dispatch;
use yii\web\Controller;
use mdm\admin\models\Assignment;
use common\models\User;
use Yii;
use mdm\admin\models\searchs\DepartmentSearch;
use mdm\admin\models\Department;

class DepartmentController extends Controller
{

    private $idField = 'id';
    private $departmentField = 'department';
    private $fullnameField = 'department';

    public function actionIndex()
    {
        $model = new Department();
        if ($model->load(Yii::$app->request->post()) && $model->save())
        {
            $model = new Department(); //reset model
        }

        $searchModel = new DepartmentSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'model' => $model,
        ]);

    }

    /**
     * Displays a single Assignment model.
     * @param  integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $model = new Dispatch($id,Department::findOne(['id'=>$id])->department);
        return $this->render('view', [
            'model' => $model,
            'idField' => $this->idField,
            'departmentField' => $this->departmentField,
        ]);
    }

    public function actionCreate()
    {
        $post = Yii::$app->request->post();
        if(isset($post['Department']['type']) && $post['Department']['type']){
            $post['Department']['type'] = implode(',', $post['Department']['type']);
        }
        $model = new Department();
        if ($model->load($post) && $model->save())
        {
            $model = new Department(); //reset model
            $this->redirect('index');
        }
        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionUpdate($id)
    {
        $model = Department::findOne($id);
        $request = Yii::$app->request;
        $post = $request->post();
        if($request->isPost){
            if($post['Department']['type']){
                $post['Department']['type'] = implode(',', $post['Department']['type']);
            }
        }else{
            if($model->type){
                $model->type = explode(',', $model->type);
            }
        }
        if ($model->load($post) && $model->save()) {
            $this->redirect('index');
        }
        return $this->render('update', [
            'model' => $model,
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
        if (($model = Department::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

}
