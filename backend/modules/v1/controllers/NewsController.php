<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-12-04
 * Time: 14:45
 */

namespace backend\modules\v1\controllers;

use backend\models\News;
use Yii;
use yii\data\ActiveDataProvider;

class NewsController extends AdminController
{
    public $modelClass = 'backend\models\News';

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
        unset($actions['index']/*, $actions['create'], $actions['update']*/);
        return $actions;
    }

    public function actionIndex()
    {
        $get = Yii::$app->request->get();
        $pageSize = isset($get['pageSize']) ? $get['pageSize'] : 10;
        $type = isset($get['type']) && $get['type'] ? $get['type'] : null;
        $title = isset($get['title']) && $get['title'] ? $get['title'] : null;
        $star = isset($get['star']) && $get['star'] ? $get['star'] : null;
        $isTop = isset($get['isTop']) && $get['isTop'] ? $get['isTop'] : null;

        $query = News::find();
        $query->andFilterWhere(["isTop" => $isTop, "star" => $star]);
        $query->andFilterWhere(['like', "title", $title]);
        if ($type == 'index') {
            return $query->orderBy('isTop DESC,updateDate DESC')->limit(10)->asArray()->all();
        } else {
            $query->orderBy('updateDate DESC');
            $provider = new ActiveDataProvider([
                'query' => $query,
                'db' => Yii::$app->db,
                'pagination' => [
                    'pageSize' => $pageSize
                ],
            ]);
            return $provider;
        }
    }



    public function actionTop(){
        $post = Yii::$app->request->post();
        $model = News::findOne($post['id']);
        if(!$model){
            return [
                'code' => 400,
                'message' => "Cant't get the model information！",
            ];
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $res = News::updateAll(['isTop' => 0],['isTop' => 1]);
            if (!$res) {
                throw new \Exception('置顶失败!');
            }
            $model->isTop = $post['isTop'];
            $ret = $model->save();
            if (!$ret) {
                throw new \Exception('置顶失败11!');
            }
            $transaction->commit();
            return true;
        } catch (\Exception $why) {
            $transaction->rollBack();
            return
            [
                'code' => 400,
                //'message' => '置顶失败！',
                'message' => $why->getMessage(),
            ];
        }

    }

}