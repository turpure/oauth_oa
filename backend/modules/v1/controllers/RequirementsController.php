<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-09-15 11:19
 */

namespace backend\modules\v1\controllers;

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
        $p_actions = parent::actions();
        // 注销系统自带的实现方法
        unset($p_actions['create'],$p_actions['update']);

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
        $name = $get['name'];
        $pageSize = isset($get['pageSize']) ? $get['pageSize'] : 10;
        $query = (new Query())->from('requirement')->where(['like', 'name', $name])
            ->orderBy('createdDate DESC');
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
        } else {
            $require->endDate = date('Y-m-d H:i:s');
            $require->status = Requirements::STATUS_DEALT;
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
        $img = isset($post['img']) ? $post['img'] : '';
        $image = Handler::common($img, 'requirement');
        $url = Yii::$app->request->hostInfo;
        $imageUrl = $image?($url . '/' . $image):'';
        try {
            $post['img'] = $imageUrl;
            $require = new Requirements();
            $require->attributes = $post;
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
        $img = isset($post['img']) ? $post['img'] : '';
        $require = Requirements::findOne($id);
        if($require->img != $post['img']){
            $image = Handler::common($img, 'requirement');
            $url = Yii::$app->request->hostInfo;
            $imageUrl = $image?($url . '/' . $image):'';
            $post['img'] = $imageUrl;
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