<?php

namespace mdm\admin\controllers;

use mdm\admin\models\Department;
use Yii;
use mdm\admin\models\form\Login;
use mdm\admin\models\form\PasswordResetRequest;
use mdm\admin\models\form\ResetPassword;
use mdm\admin\models\form\Signup;
use mdm\admin\models\form\CreateUser;
use mdm\admin\models\form\UpdateUser;
use mdm\admin\models\form\ChangePassword;
use mdm\admin\models\User;
use mdm\admin\models\searchs\User as UserSearch;
use yii\base\InvalidParamException;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\base\UserException;
use yii\mail\BaseMailer;
/**
 * User controller
 */
class UserController extends Controller
{
    private $_oldMailPath;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                    'logout' => ['post'],
                    'activate' => ['post'],
                    'auto-signup' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (parent::beforeAction($action)) {
            if (Yii::$app->has('mailer') && ($mailer = Yii::$app->getMailer()) instanceof BaseMailer) {
                /* @var $mailer BaseMailer */
                $this->_oldMailPath = $mailer->getViewPath();
                $mailer->setViewPath('@mdm/admin/mail');
            }
            return true;
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function afterAction($action, $result)
    {
        $method = $action->actionMethod;
        if($action->actionMethod === 'actionAutoSignup') {
            \Yii::$app->response->format = "json";
//            $result = parent::afterAction($action, $result);
            $data['code'] = isset($result['code']) ? $result['code'] : 200;
            $data['message'] = isset($result['message']) ? $result['message'] : '操作成功';
            if ($result === null) {
                $result = [];
            }
            if ($data['code'] == 200 && (is_array($result))) {
                $data['data'] = $result;
            }
            if ($result === false) {
                $data['code'] = 400;
                $data['message'] = '操作失败';
            }
            return $data;
        }
        else{
            if ($this->_oldMailPath !== null) {
                Yii::$app->getMailer()->setViewPath($this->_oldMailPath);
            }
            return parent::afterAction($action, $result);
        }

    }

    /**
     * Lists all User models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new UserSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
                'searchModel' => $searchModel,
                'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Create a user
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new CreateUser();

        if ($model->load(Yii::$app->getRequest()->post())) {
            if ($user = $model->create()) {
                $this->redirect('index');
            }
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }
    /**
     * Displays a single User model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
                'model' => $this->findModel($id),
        ]);
    }


    /**
     * update a single User model.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = new UpdateUser($id);

        if ($model->load(Yii::$app->getRequest()->post())) {
            if ($user = $model->save()) {
                $this->redirect('index');
            }
        }
//print_r($model);exit;

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * 获取各级部门列表
     * @param $id
     * @return string
     */
    public function actionAjax()
    {
        $id = Yii::$app->request->post('id',0);
        $depart = Department::find()->andFilterWhere(['parent' =>$id])->asArray()->all();
        return json_encode($depart);
    }

    /**
     * Deletes an existing User model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Login
     * @return string
     */
    public function actionLogin()
    {
        if (!Yii::$app->getUser()->isGuest) {
            return $this->goHome();
        }

        $model = new Login();
        if ($model->load(Yii::$app->getRequest()->post()) && $model->login()) {
            return $this->goBack();
        } else {
            return $this->render('login', [
                    'model' => $model,
            ]);
        }
    }

    /**
     * Logout
     * @return string
     */
    public function actionLogout()
    {
        Yii::$app->getUser()->logout();

        return $this->goHome();
    }

    /**
     * Signup new user
     * @return string
     */
    public function actionSignup()
    {
        $model = new Signup();

        if ($model->load(Yii::$app->getRequest()->post())) {
            $post = Yii::$app->getRequest()->post();
            if ($user = $model->signup()) {
                return $this->goHome();
            }
        }

        return $this->render('signup', [
                'model' => $model,
        ]);
    }

    /**
     * Signup new user
     * @return string
     */
    public function actionAutoSignup()
    {
        $model = new Signup();
//        $default_users = [["Signup"=>['username'=>'test','email'=>'test@666.com', 'password'=>'test666']]];
        $users = isset(Yii::$app->getRequest()->post()['users'])?Yii::$app->getRequest()->post()['users']:[];
        $users = json_decode($users,true);
        try {
            foreach ($users as $person) {
                $model = clone $model;
                $model->load($person);
                if(!$model->signup()){
                    throw new \Exception('自动注册失败！');
                }
            }
            $ret = '自动注册成功！';
        }
        catch (\Exception $why) {
            $ret = '自动注册失败！';
        }
        return [$ret];

    }

    /**
     * Request reset password
     * @return string
     */
    public function actionRequestPasswordReset()
    {
        $model = new PasswordResetRequest();
        if ($model->load(Yii::$app->getRequest()->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->getSession()->setFlash('success', 'Check your email for further instructions.');

                return $this->goHome();
            } else {
                Yii::$app->getSession()->setFlash('error', 'Sorry, we are unable to reset password for email provided.');
            }
        }

        return $this->render('requestPasswordResetToken', [
                'model' => $model,
        ]);
    }

    /**
     * Reset password
     * @return string
     */
    public function actionResetPassword($token)
    {
        try {
            $model = new ResetPassword($token);
        } catch (InvalidParamException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if ($model->load(Yii::$app->getRequest()->post()) && $model->validate() && $model->resetPassword()) {
            Yii::$app->getSession()->setFlash('success', 'New password was saved.');

            return $this->goHome();
        }

        return $this->render('resetPassword', [
                'model' => $model,
        ]);
    }

    /**
     * Reset password
     * @return string
     */
    public function actionChangePassword()
    {
        $model = new ChangePassword();
        if ($model->load(Yii::$app->getRequest()->post()) && $model->change()) {
            return $this->goHome();
        }

        return $this->render('change-password', [
                'model' => $model,
        ]);
    }

    /**
     * Activate new user
     * @param integer $id
     * @return type
     * @throws UserException
     * @throws NotFoundHttpException
     */
    public function actionActivate($id)
    {
        /* @var $user User */
        $user = $this->findModel($id);
        if ($user->status == User::STATUS_INACTIVE) {
            $user->status = User::STATUS_ACTIVE;
            if ($user->save()) {
                return $this->redirect(['index']);
            } else {
                $errors = $user->firstErrors;
                throw new UserException(reset($errors));
            }
        }
        return $this->redirect(['index']);
    }


    /**
     * Activate new user
     * @param integer $id
     * @return type
     * @throws UserException
     * @throws NotFoundHttpException
     */
    public function actionInactivate($id)
    {
        /* @var $user User */
        $user = $this->findModel($id);
        if ($user->status == User::STATUS_ACTIVE) {
            $user->status = User::STATUS_INACTIVE;
            if ($user->save()) {
                return $this->redirect(['index']);
            } else {
                $errors = $user->firstErrors;
                throw new UserException(reset($errors));
            }
        }
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
        if (($model = User::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * @brief user表单验证
     * @param $id int
     * @return array
     */
    public function actionValidateUser ($id) {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $model = new UpdateUser($id);
        $model->load(Yii::$app->request->post());
        return \yii\widgets\ActiveForm::validate($model);
    }


}
