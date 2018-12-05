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
        if($model['isTop'] == $post['isTop']){
            return [
                'code' => 400,
                'message' => $post['isTop']?'该信息已处置顶状态，不能进行置顶操作！':'该信息已处非置顶状态，不能进行取消置顶操作！',
            ];
        }
        $model->isTop = $post['isTop'];
        $res = $model->save();
        //$res = News::updateAll(['isTop' => $post['isTop']],['id' => $post['id']]);

        if($res) {
            return true;
        }else{
            return [
                'code' => 400,
                'message' => $post['isTop']?'置顶失败！':'取消置顶失败！',
            ];
        }
    }

}