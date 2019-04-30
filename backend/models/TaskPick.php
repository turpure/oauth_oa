<?php

namespace backend\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
/**
 * This is the model class for table "task_expressTracking".
 *
 * @property string $id
 * @property string $batchNumber
 * @property string $picker
 * @property string $createdTime
 * @property string $scanningMan
 * @property integer $isDone
 */
class TaskPick extends \yii\db\ActiveRecord
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
        return 'task_pick';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['createdTime'], 'safe'],
            [['isDone'], 'integer'],
            [['batchNumber'], 'string', 'max' => 50],
            [['scanning'], 'string', 'max' => 20],
            [['picker'], 'string', 'max' => 10],
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
            'picker' => 'Picker',
            'scanningMan' => 'scanning Man',
            'createdTime' => 'Created Time',
        ];
    }
}
