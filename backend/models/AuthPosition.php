<?php

namespace backend\models;

use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "auth_position".
 *
 * @property int $id
 * @property string $position
 */
class AuthPosition extends \yii\db\ActiveRecord
{
    const JOB_MANAGER = '经理';
    const JOB_CHARGE = '主管';
    const JOB_DEVELOP = '开发';
    const JOB_PURCHASE = '采购';
    const JOB_ART = '美工';
    const JOB_SALE = '销售';
    const JOB_SERVICE = '客服';
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'auth_position';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['position'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'position' => 'Position',
        ];
    }

    public static function getPosition($id){
        $list = (new Query())->select('p.position')
            ->from('auth_position_child pc')
            ->leftJoin('auth_position p', 'p.id=pc.position_id')
            ->where(['user_id' => $id])->all();
        return ArrayHelper::getColumn($list,'position');
    }



}
