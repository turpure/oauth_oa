<?php

namespace backend\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
/**
 * This is the model class for table "task_label".
 *
 * @property string $id
 * @property string $batchNumber
 * @property string $username
 * @property string $createdTime
 * @property string $scanningMan
 * @property integer $isDone
 */
class TaskLabel extends \yii\db\ActiveRecord
{

    public function behaviors()
    {
        return [[
            /**
             * TimestampBehavior：
             */
            'class' => TimestampBehavior::className(),
            'createdAtAttribute' => 'createdTime',
            'updatedAtAttribute' => 'updatedTime',
            'value' => new Expression('NOW()'),
        ],];
    }
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'task_label';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['createdTime', 'updatedTime'], 'safe'],
            [['isDone'], 'integer'],
            [['batchNumber'], 'string', 'max' => 50],
            [['scanningMan'], 'string', 'max' => 20],
            [['username'], 'string', 'max' => 10],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'batchNumber' => 'Batch Number',
            'username' => 'Username',
            'scanningMan' => 'scanning Man',
            'createdTime' => 'Created Time',
        ];
    }
}
