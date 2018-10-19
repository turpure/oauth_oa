<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-09-15 11:19
 */

namespace backend\modules\v1\controllers;

use backend\models\AuthAssignment;
use backend\models\Requirements;
use backend\modules\v1\utils\Handler;
use common\models\User;
use yii\helpers\ArrayHelper;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\Query;

class RequirementsController extends AdminController
{
    public $modelClass = 'backend\models\Requirements';

    public $isRest = true;

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];

    /**
     * @brief set pageSize
     */
    public function actions()
    {
        $p_actions = parent::actions();
        // 注销系统自带的实现方法
        unset($p_actions['create'], $p_actions['update']);

        $actions = ArrayHelper::merge(
        //parent::actions(),
            $p_actions,
            [
                'index' => [
                    'prepareDataProvider' => function ($action) {
                        /* @var $modelClass \yii\db\BaseActiveRecord */
                        $modelClass = $action->modelClass;

                        return Yii::createObject([
                            'class' => ActiveDataProvider::className(),
                            'query' => $modelClass::find()->orderBy('createdDate DESC'),
                            //'pagination' => false,
                            'pagination' => [
                                'pageSize' => 10,
                            ],
                        ]);
                    },
                ],
            ]
        );

        return $actions;
    }

    public function actionSearchRequirements()
    {
        $get = Yii::$app->request->get();
        $pageSize = isset($get['pageSize']) ? $get['pageSize'] : 10;
        $type = isset($get['type']) && $get['type'] ? $get['type'] : null;
        $priority = isset($get['priority']) && $get['priority'] ? $get['priority'] : null;
        $status = isset($get['status']) && $get['status'] ? $get['status'] : null;
        $user = $this->authenticate(Yii::$app->user, Yii::$app->request, Yii::$app->response);
        $role = (new Query())->select('item_name as role')->from('auth_assignment')->where(['user_id' => $user->id])->all();
        $roleList = ArrayHelper::getColumn($role,'role');
        //print_r($roleList);exit;
        $query = (new Query())->from('requirement');
        if(!in_array(AuthAssignment::ACCOUNT_ADMIN,$roleList)){
            $query->andFilterWhere([
                "OR",
                ['creator' => $user->username],
                ['auditor' => $user->username],
                ['processingPerson' => $user->username],
            ]);
        }
        $query->andFilterWhere(["type" => $type, "priority" => $priority, "status" => $status]);
        $query->andFilterWhere(["type" => $type, "priority" => $priority, "status" => $status]);
        $query->andFilterWhere(['like', "processingPerson", $get['processingPerson']]);
        if ($get['flag'] == 'name') {
            $query->andFilterWhere(['like', "name", $get['name']]);
        } else {
            $query->andFilterWhere(['like', "creator", $get['name']]);
        }
        $query->orderBy('createdDate DESC');

        $provider = new ActiveDataProvider([
            'query' => $query,
            'db' => Yii::$app->db,
            'pagination' => [
                'pageSize' => $pageSize
            ],
        ]);

        //print_r($query->createCommand()->getRawSql());exit;
        return $provider;
    }

    /**
     * 审核
     * @return array
     * @throws \yii\db\Exception
     */
    public function actionExamine()
    {
        $post = $get = Yii::$app->request->post('condition', '');
        $ids = isset($post['ids']) ? $post['ids'] : [];
        if (!$ids) return ['code' => 400, 'message' => '请选择要审核的项目！',];
        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach ($ids as $id) {
                $user = $this->authenticate(Yii::$app->user, Yii::$app->request, Yii::$app->response);
                $username = $user->username;
                $require = Requirements::findOne($id);
                $require->auditor = $username;
                $require->auditDate = date('Y-m-d H:i:s');
                $require->status = Requirements::STATUS_TO_BE_DEALT;
                if (!$require->save()) {
                    throw new \Exception('failed to examine requirements!');
                }
                //发邮件给创建人
                $c_user= User::findOne(['username' => $require->creator]);
                if($c_user && $c_user->email){
                    $content = '<div>'.
                        $require->creator.'<p style=" text-indent:2em;">你好:</p>
                        <p style="text-indent:2em;">您的需求建议<span style="font-size:150%;color: blue;">'.$require->name. '</span>已通过审核!
                        详情请查看:<a href="http://58.246.226.254:9099/?#/v1/requirements/index">http://58.246.226.254:9099</a></p></div>';
                    Handler::email($user->email,'UR管理中心需求进度变更',$content);
                }
                //发邮件给处理人
                $d_user= User::findOne(['username' => $require->processingPerson]);
                if($d_user && $d_user->email){
                    $content = '<div>'.
                        $require->processingPerson.'<p style=" text-indent:2em;">你好:</p>
                        <p style="text-indent:2em;">您有新的需求建议：<span style="font-size:150%;color: blue;">'.$require->name. '</span>，请及时处理!
                        详情请查看:<a href="http://58.246.226.254:9099/?#/v1/requirements/index">http://58.246.226.254:9099</a></p></div>';
                    Handler::email($user->email,'UR管理中心需求进度变更',$content);
                }
            }
            $transaction->commit();
            $res = ['code' => 200, 'message' => '审批完成！'];
        } catch (\Exception $e) {
            $transaction->rollBack();
            $res = ['code' => 400, 'message' => '审批失败！'];
            $res = ['code' => 400, 'message' => $e->getMessage()];
        }
        return $res;
    }

    /**
     * 处理人 开始或结束操作
     * @return array
     */
    public function actionDeal()
    {
        $post = $get = Yii::$app->request->post('condition', '');
        if (!($post['id'] && $post['type'])) return ['code' => 400, 'message' => '参数不能为空！',];
        $require = Requirements::findOne($post['id']);
        $user = $this->authenticate(Yii::$app->user, Yii::$app->request, Yii::$app->response);
        $username = $user->username;
        $require->processingPerson = $username;
        if ($post['type'] === 'begin') {
            $require->beginDate = date('Y-m-d H:i:s');
            $require->status = Requirements::STATUS_DEALING;
            $content = '<div>'.
                $require->creator.'<p style=" text-indent:2em;">你好:</p>
                        <p style="text-indent:2em;">您的需求建议已被<span style="font-size:150%;color: blue;">'.$require->processingPerson. '</span>接受处理！
                        详情请查看:<a href="http://58.246.226.254:9099/?#/v1/requirements/index">http://58.246.226.254:9099</a></p></div>';
        } else {
            $require->endDate = date('Y-m-d H:i:s');
            $require->status = Requirements::STATUS_DEALT;
            $content = '<div>'.
                $require->creator.'<p style=" text-indent:2em;">你好:</p>
                        <p style="text-indent:2em;">您的需求建议已被<span style="font-size:150%;color: blue;">'.$require->processingPerson. '</span>处理完成!
                        详情请查看:<a href="http://58.246.226.254:9099/?#/v1/requirements/index">http://58.246.226.254:9099</a></p></div>';
        }
        $c_user= User::findOne(['username' => $require->creator]);
        if($c_user && $c_user->email){
            Handler::email($user->email,'UR管理中心需求进度变更',$content);
        }
        if ($require->save()) {
            return ['code' => 200, 'message' => '操作成功！'];
        } else {
            return ['code' => 400, 'message' => '操作失败！'];
        }
    }

    /**
     * 新建
     * @return array|Requirements
     */
    public function actionCreate()
    {
        $post = Yii::$app->request->post();
        $url = Yii::$app->request->hostInfo;
        $user = $this->authenticate(Yii::$app->user, Yii::$app->request, Yii::$app->response);
        $imgArr = isset($post['img']) && $post['img'] ? $post['img'] : [];
        $image = '';
        if($imgArr){
            foreach ($imgArr as $v){
                $img = Handler::common($v, 'requirement');
                $imageList[] = $img ? ($url . '/' . $img) : '';
            }
            $image = implode(',',$imageList);
        }
        try {
            $post['img'] = $image;
            $require = new Requirements();
            $require->attributes = $post;
            $require->creator = $user->username;
            $require->save();
            return $require;
        } catch (\Exception $why) {
            return [$why];
        }
    }

    /**
     * 编辑
     * @return array|Requirements
     */
    public function actionUpdate($id)
    {
        $post = Yii::$app->request->post();
        $url = Yii::$app->request->hostInfo;
        $imgArr = isset($post['img']) ? $post['img'] : '';
        $require = Requirements::findOne($id);
        if ($require->img != $imgArr) {
            $image = '';
            if($imgArr){
                foreach ($imgArr as $v){
                    $img = Handler::common($v, 'requirement');
                    $imageList[] = $img ? ($url . '/' . $img) : '';
                }
                $image = implode(',',$imageList);
            }
            $post['img'] = $image;
        }
        try {
            $require->attributes = $post;
            $require->save();
            return $require;
        } catch (\Exception $why) {
            return [$why];
        }
    }


}