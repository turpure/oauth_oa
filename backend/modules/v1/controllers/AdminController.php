<?php
/**
 * @desc base controller.
 * @author: turpure
 * @since: 2018-05-17 15:53
 */

namespace backend\modules\v1\controllers;

use backend\models\AuthAssignment;
use common\models\User;
use yii\rest\ActiveController;
use yii\helpers\ArrayHelper;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\filters\Cors;
use yii\base\UserException;
use Yii;

class AdminController extends ActiveController
{
    public $isRest = false;

    public function actions()
    {
        $actions = parent::actions();
        // 注销系统自带的实现方法
        if (!$this->isRest) {
            unset($actions['index'], $actions['update'], $actions['create'], $actions['delete'], $actions['view']);
        }
        return $actions;
    }

    public function behaviors()
    {
        $behaviors = ArrayHelper::merge([
            [
                'class' => Cors::className(),
                'cors' => [
                    'Origin' => ['*'],
                    'Access-Control-Allow-Credentials' => true,
                    'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'OPTIONS','DELETE'],
                    'Access-Control-Request-Headers' => ['*'],
                    'Access-Control-Max-Age' => 86400,
                    'Access-Control-Expose-Headers' => [],
                ],
            ],
        ],
            parent::behaviors()
        );


        $behaviors['authenticator'] = [
            'class' => CompositeAuth::className(),

            'authMethods' => [
                ['class' => HttpBasicAuth::className(), 'auth' => [$this, 'auth']],
                ['class' => HttpBearerAuth::className()],
                ['class' => QueryParamAuth::className(), 'tokenParam' => 'token',],

            ],
            'optional' => [
                'login',
                'signup'
            ]
        ];

        return $behaviors;
    }


    /**
     * @brief basic-auth auth
     * @param $username, $password
     * @return mixed
     * @throws \Exception
     */
    public function auth($username, $password)
    {
        $user = User::findByUsername($username);
        if (empty($username) || empty($password) || empty($user)) {
            throw new UserException('There is an error!');
        }
        if ($user->validatePassword($password)) {
            return $user;
        }
        throw new UserException('Wrong username or password!');
    }


    /**
     * @inheritdoc
     */
    public function afterAction($action, $result)
    {
        \Yii::$app->response->format = "json";
        $result = parent::afterAction($action, $result);
        $data['code'] = isset($result['code']) ? $result['code'] : 200;
        $data['message'] = isset($result['message']) ? $result['message'] : 'success';
        if ($result === null) {
            $result = [];
        }
//        if ($data['code'] === 200 && $result && $result !== true) {
//            $data['data'] = $result;
//        }
        if ($data['code'] === 200 ) {
            $data['data'] = $result;
        }
        if ($result === false) {
            $data['code'] = 400;
            $data['message'] = 'error';
        }
        return $this->serializeData($data);
    }


    /**
     * @param $action
     * @return bool
     * @throws \Exception
     */
    public function beforeAction($action)
    {
        if (parent::beforeAction($action)) {
            if($this->action->id !== 'login'){
                $this->checkAccess($this->action->id);
            }
            return true;
        }
        return false;
    }


    /**
     * @param string $action
     * @param null $model
     * @param array $params
     * @return mixed
     * @throws \yii\db\Exception
     * @throws \yii\web\ForbiddenHttpException
     */

    public function checkAccess($action, $model = null, $params = [])
    {
        $userId = Yii::$app->user->id;
        $db = Yii::$app->db;
        $role = User::getRole($userId);
        //if ($role !== AuthAssignment::ACCOUNT_ADMIN) {
        if (in_array(AuthAssignment::ACCOUNT_ADMIN,$role) === false) {
            $actionId = '/' . Yii::$app->controller->getRoute();
            $check_sql = 'select usr.id as userId,item.child as actionId from `user` as usr
                  LEFT JOIN `auth_assignment` as ass on usr.id=ass.user_id
                  LEFT JOIN `auth_item_child` as item on item.parent=ass.item_name where usr.id=:userId';
            $user_permission = $db->createCommand($check_sql, [':userId' => $userId])->queryAll();
            $auth_actions = [];
            foreach ($user_permission as $row) {
                $auth_actions[] = $row['actionId'];
            }
            if (!in_array($actionId, $auth_actions)) {
                throw new \yii\web\ForbiddenHttpException("No permission!");
            }
        }
    }

}
