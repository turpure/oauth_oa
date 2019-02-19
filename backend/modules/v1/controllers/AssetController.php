<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-02-18
 * Time: 8:55
 * Author: henry
 */
/**
 * @name AssetController.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2019-02-18 8:55
 */


namespace backend\modules\v1\controllers;


class AssetController extends AdminController
{
    public $modelClass = 'backend\models\Requirements';

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];


    public function actionIndex(){

    }


}