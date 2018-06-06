<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-05-17 15:53
 */

namespace backend\modules\v1\controllers;
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
use yii\filters\ContentNegotiator;
use yii\web\Response;

class AdminController extends ActiveController
{

//    public function init()
//    {
//        parent::init();
//        Yii::$app->user->enableSession = false;
//        Yii::$app->user->loginUrl  = false;
//    }


    public function behaviors()
    {
        $behaviors = ArrayHelper::merge([
            [
                'class' => Cors::className(),
                'cors' => [
                    'Origin' => ['*'],
                    'Access-Control-Allow-Credentials' => true,
                    'Access-Control-Request-Headers' => ['Origin', 'X-Requested-With', 'Content-Type', 'Accept'],
                ],
            ],
        ],
            parent::behaviors()
        );


//        $behaviors['contentNegotiator'] = [
//            'class' => ContentNegotiator::className(),
//            'formats' => [
//                'application/json' => Response::FORMAT_JSON
//            ]
//        ];

        $behaviors['authenticator'] = [
            'class' => CompositeAuth::className(),

            'authMethods'        => [
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


    /*
     * basic-auth auth
     */
    public function auth($username, $password)
    {
        $user = User::findByUsername($username);
        if(empty($username) || empty($password) || empty($user)) {
            //return false;
            //OR
            throw new UserException("There is an error!");
        }
        if ($user->validatePassword($password)) {
            return $user;
        }
        //return false;
        //OR
        throw new UserException("Wrong username or password!");
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
        if ($data['code'] == 200 && (is_array($result))) {
            $data['data'] = $result;
        }
        if ($result === false) {
            $data['code'] = 400;
            $data['message'] = 'error';
        }

        return $this->serializeData($data);
    }


    /*
     * check access
     */
    public function checkAccess($action, $model = null, $params = [])
    {
//        $user = $this->authenticate(Yii::$app->user, Yii::$app->request, Yii::$app->response);
//        $userId = $user?$user->getId():'';
        $userId = Yii::$app->user->id;
        $db = Yii::$app->db;
        $actionId = '/'.Yii::$app->controller->getRoute();
        $check_sql = 'select usr.id as userId,item.child as actionId from `user` as usr
                  LEFT JOIN `auth_assignment` as ass on usr.id=ass.user_id
                  LEFT JOIN `auth_item_child` as item on item.parent=ass.item_name where usr.id=:userId';
        $user_permission = $db->createCommand($check_sql,[':userId'=>$userId])->queryAll();
        $auth_actions= [];
        foreach ($user_permission as $row){
              $auth_actions[] = $row['actionId'];
        }
        if ($model->creator_id !== $userId) {
            throw new \yii\web\ForbiddenHttpException("You can only '.$action.' articles that you've created.");
        }
        if(!in_array($actionId,$auth_actions)){
            throw new \yii\web\ForbiddenHttpException("No permiession!");
        }
    }
}