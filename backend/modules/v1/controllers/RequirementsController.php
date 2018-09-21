<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-09-15 11:19
 */

namespace backend\modules\v1\controllers;

use yii\helpers\ArrayHelper;
use Yii;
use yii\data\ActiveDataProvider;

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
        $actions = ArrayHelper::merge(
            parent::actions(),
            [
                'index' => [
                    'prepareDataProvider' => function ($action) {
                        /* @var $modelClass \yii\db\BaseActiveRecord */
                        $modelClass = $action->modelClass;

                        return Yii::createObject([
                            'class' => ActiveDataProvider::className(),
                            'query' => $modelClass::find(),
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

}