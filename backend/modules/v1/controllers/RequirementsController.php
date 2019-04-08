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
        $actions = parent::actions();
        // 注销系统自带的实现方法
        unset($actions['index'], $actions['create'], $actions['update']);
        return $actions;
    }


    /**
     * 我的需求列表
     * @return ActiveDataProvider
     */
    public function actionIndex()
    {
        $get = Yii::$app->request->get();
        $sortProperty = !empty($get['sortProperty']) ? $get['sortProperty'] : 'id';
        $sortOrder = !empty($get['sortOrder']) ? $get['sortOrder'] : 'desc';
        $pageSize = isset($get['pageSize']) ? $get['pageSize'] : 10;
        $type = $get['type'];//isset($get['type']) && $get['type'] ? $get['type'] : null;
        $priority = isset($get['priority']) && $get['priority'] ? $get['priority'] : null;
        $schedule = isset($get['schedule']) && $get['schedule'] ? $get['schedule'] : null;
        $user = $this->authenticate(Yii::$app->user, Yii::$app->request, Yii::$app->response);
        $role = (new Query())->select('item_name as role')->from('auth_assignment')->where(['user_id' => $user->id])->all();
        $roleList = ArrayHelper::getColumn($role, 'role');
        $query = (new Query())->from('requirement');
        if (!in_array(AuthAssignment::ACCOUNT_ADMIN, $roleList)) {
            $query->andFilterWhere(['creator' => $user->username]);
        }
        $query->andFilterWhere(["type" => $type, "priority" => $priority, "schedule" => $schedule]);
        $query->andFilterWhere(['like', "creator", $get['creator']]);
        $query->andFilterWhere(['like', "name", $get['name']]);
        $query->andFilterWhere(['like', "detail", $get['detail']]);
        $query->orderBy($sortProperty.' '.$sortOrder);
        $provider = new ActiveDataProvider([
            'query' => $query,
            'db' => Yii::$app->db,
            'pagination' => [
                'pageSize' => $pageSize
            ],
        ]);
        return $provider;
    }

    /**
     * 审核列表
     * @return ActiveDataProvider
     */
    public function actionExamineList()
    {
        $get = Yii::$app->request->get();
        $pageSize = isset($get['pageSize']) ? $get['pageSize'] : 10;
        $type = $get['type'];//isset($get['type']) && $get['type'] ? $get['type'] : null;
        $priority = isset($get['priority']) && $get['priority'] ? $get['priority'] : null;

        $query = (new Query())->from('requirement');
        $query->andFilterWhere(["type" => $type, "priority" => $priority, "schedule" => Requirements::SCHEDULE_TO_BE_AUDITED]);
        $query->andFilterWhere(['like', "name", $get['name']]);
        $query->andFilterWhere(['like', "detail", $get['detail']]);
        $query->andFilterWhere(['like', "creator", $get['creator']]);
        $query->andFilterWhere(['like', "processingPerson", $get['processingPerson']]);

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
     * 处理
     * @return ActiveDataProvider
     */
    public function actionDealList()
    {
        $get = Yii::$app->request->get();
        $sortProperty = !empty($get['sortProperty']) ? $get['sortProperty'] : 'id';
        $sortOrder = !empty($get['sortOrder']) ? $get['sortOrder'] : 'desc';
        $pageSize = isset($get['pageSize']) ? $get['pageSize'] : 10;
        $type = $get['type'];//isset($get['type']) && $get['type'] ? $get['type'] : null;
        $priority = isset($get['priority']) && $get['priority'] ? $get['priority'] : null;
        $status = isset($get['status']) && $get['status'] ? $get['status'] : null;

        $query = (new Query())->from('requirement');
        $query->andFilterWhere(["type" => $type, "priority" => $priority, 'status' => $status]);
        $query->andFilterWhere(["schedule" => [Requirements::SCHEDULE_DEALING, Requirements::SCHEDULE_DEALT]]);
        $query->andFilterWhere(['like', "processingPerson", $get['processingPerson']]);
        $query->andFilterWhere(['like', "name", $get['name']]);
        $query->andFilterWhere(['like', "detail", $get['detail']]);
        $query->andFilterWhere(['like', "creator", $get['creator']]);
        $query->orderBy($sortProperty.' '.$sortOrder);

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
                if ($require->schedule != Requirements::SCHEDULE_TO_BE_AUDITED) {
                    throw new \Exception('Can not examine the requirement again, please choose again!');
                }
                if (!$require->processingPerson) {
                    throw new \Exception("Can not examine the requirement again, because there's no dealer!");
                }
                $require->auditor = $username;
                $require->auditDate = date('Y-m-d H:i:s');
                $require->schedule = $post['type'] == 'pass' ? Requirements::SCHEDULE_DEALING : Requirements::SCHEDULE_FAILED;
                $require->status = $post['type'] == 'pass' ? 1 : 0;
                if (!$require->save()) {
                    throw new \Exception('failed to examine requirements!');
                }
            }
            $transaction->commit();
            $res = [];
        } catch (\Exception $e) {
            $transaction->rollBack();
            $res = ['code' => 400, 'message' => $e->getMessage()];
        }
        return $res;
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
        $image = $src = '' ;

        if ($imgArr) {
            foreach ($imgArr as $v) {
                $img = Handler::common($v, 'requirement');
                $item = $img ? ($url . '/' . $img) : '';
                $src = '<img src="' . $item . '">';
                $post['detail'] =  preg_replace("/<img src=\"\">/", $src, $post['detail'],1);
                $imageList[] = $item;
            }
            $image = implode(',', $imageList);
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
        $imgArr = isset($post['img']) ? $post['img'] : [];
        if(is_array($imgArr)) ksort($imgArr);
        $require = Requirements::findOne($id);
        $oldStatus = $require->status;
        if ($require->img != $imgArr) {
            $image = $src = '';
            if ($imgArr) {
                foreach ($imgArr as $v) {
                    if(strpos($v,"http://") === 0){
                        $item = $v;
                    }else{
                        $img = Handler::common($v, 'requirement');
                        $item = $img ? ($url . '/' . $img) : '';
                    }
                    $src = '<img src="' . $item . '">';
                    $post['detail'] =  preg_replace("/<img src=\"\">/", $src, $post['detail'],1);
                    $imageList[] = $item;
                }
                $image = implode(',', $imageList);
            }
            $post['img'] = $image;
        }
        try {
            $require->attributes = $post;
            if ($require->status == 5 && $oldStatus != 5 && $require->schedule == Requirements::SCHEDULE_DEALING) {
                $require->schedule = Requirements::SCHEDULE_DEALT;
                $require->endDate = date('Y-m-d H:i:s');
            }
            if ($require->status != 5 && $oldStatus == 5 && $require->schedule == Requirements::SCHEDULE_DEALT) {
                $require->schedule = Requirements::SCHEDULE_DEALING;
            }
            $require->save();

            return $require;
        } catch (\Exception $why) {
            return [$why];
        }
    }


}