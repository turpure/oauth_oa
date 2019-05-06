<?php

namespace backend\models;

use backend\modules\v1\utils\MailEvent;
use common\models\User;
use Yii;
use yii\behaviors\TimestampBehavior;
use \yii\db\ActiveRecord;

/**
 * This is the model class for table "oauth_requirements".
 *
 * @property int $id
 * @property string $name
 * @property string $detail
 * @property string $creator
 * @property int $type
 * @property int $priority
 * @property string $createdDate
 * @property string $auditDate
 * @property string $beginDate
 * @property string $endDate
 * @property string $finishedDate
 * @property int $status
 * @property string $result
 * @property string $feedBack
 * @property string $processingPerson
 * @property string $deadline
 * @property string $auditor
 * @property string $schedule
 */
class Requirements extends ActiveRecord
{
    const SCHEDULE_TO_BE_AUDITED = 1;     //待审核
    const SCHEDULE_FAILED = 2;     //已驳回
    const SCHEDULE_DEALING = 3;     //处理中
    const SCHEDULE_DEALT = 4;     //处理完成

    const EVENT_SEND_EMAIL = 'send_email';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        //return 'oauth_requirements';
        return 'requirement';
    }

    public function init ()
    {
        parent::init();
        // 绑定邮件类，当事件触发的时候，调用我们刚刚定义的邮件类Mail
        $this->on(self::EVENT_SEND_EMAIL, ['backend\modules\v1\utils\Handler', 'email']);
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db');
    }

    /**
     * @brief set behaviors
     *
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(),
            [
                [
                    'class' => TimestampBehavior::className(),
                    'attributes' => [
                        # 创建之前
                        ActiveRecord::EVENT_BEFORE_INSERT => ['createdDate'],
                    ],
                    #设置默认值
                    'value' => date('Y-m-d H:i:s')
                ]
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'detail', 'creator', 'auditor', 'result', 'feedBack', 'processingPerson', 'img'], 'string'],
            [['priority', 'type', 'status', 'schedule'], 'integer'],
            [['createdDate', 'beginDate', 'endDate', 'auditDate','deadline'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'detail' => 'Detail',
            'img' => 'Img',
            'creator' => 'Creator',
            'type' => 'Type',
            'priority' => 'Priority',
            'createdDate' => 'Created Date',
            'beginDate' => 'Begin Date',
            'endDate' => 'End Date',
            'auditor' => 'Auditor',
            'auditDate' => 'Audit Date',
            'schedule' => 'Schedule',
            'status' => 'Status',
            'result' => 'Result',
            'feedBack' => 'Feed Back',
            'processingPerson' => 'Processing Person',
        ];
    }

    /*
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if (!isset($changedAttributes['schedule'])) {
            return true;
        }
        //审核通过，发邮件给处理人
        if ($changedAttributes['schedule'] == self::SCHEDULE_DEALING) {
            $arr = explode(',', $this->processingPerson);
            if($arr){
                foreach ($arr as $v){
                    $d_user = User::findOne(['username' => $v]);
                    if ($d_user && $d_user->email) {
                        $c_event = new MailEvent();
                        $c_event->email = $d_user->email;
                        $c_event->subject = 'UR管理中心需求进度变更';
                        $c_event->content = '<div>' .
                            $v . '<p style=" text-indent:2em;">你好:</p>
                                <p style="text-indent:2em;">您有新的需求建议：<span style="font-size:150%;color: blue;">' . $this->name . '</span>，请及时处理!
                                详情请查看:<a href="http://58.246.226.254:9099/?#/v1/requirements/index">http://58.246.226.254:9099</a></p></div>';
                        $this->trigger(self::EVENT_SEND_EMAIL,$c_event);
                    }
                }
            }
        }

        //处理完成且是多个处理人时，发邮件给处理人
        if ($changedAttributes['schedule'] == self::SCHEDULE_DEALT) {
            $arr = explode(',', $this->processingPerson);
            if($arr && count($arr) > 1){
                foreach ($arr as $v){
                    $d_user = User::findOne(['username' => $v]);
                    if ($d_user && $d_user->email) {
                        $c_event = new MailEvent();
                        $c_event->email = $d_user->email;
                        $c_event->subject = 'UR管理中心需求进度变更';
                        $c_event->content = '<div>' .
                            $v . '<p style=" text-indent:2em;">你好:</p>
                                <p style="text-indent:2em;">您的需求建议：<span style="font-size:150%;color: blue;">' . $this->name . '</span>，已经处理完成!
                                详情请查看:<a href="http://58.246.226.254:9099/?#/v1/requirements/index">http://58.246.226.254:9099</a></p></div>';
                        $this->trigger(self::EVENT_SEND_EMAIL,$c_event);
                    }
                }
            }
        }


        //发邮件给创建人
        $c_user = User::findOne(['username' => $this->creator]);
        if ($c_user && $c_user->email) {
            $event = new MailEvent();
            $event->email = $c_user->email;
            $event->subject = 'UR管理中心需求进度变更';
            if($changedAttributes['schedule'] == self::SCHEDULE_FAILED){
                $event->content = '<div>' .
                    $this->creator . '<p style=" text-indent:2em;">你好:</p>
                        <p style="text-indent:2em;">您的需求建议<span style="font-size:150%;color: blue;">' . $this->name . '</span>未通过审核!
                        详情请查看:<a href="http://58.246.226.254:9099/?#/v1/requirements/index">http://58.246.226.254:9099</a></p></div>';
            }elseif ($changedAttributes['schedule'] == self::SCHEDULE_DEALING){
                $event->content = '<div>' .
                    $this->creator . '<p style=" text-indent:2em;">你好:</p>
                        <p style="text-indent:2em;">您的需求建议<span style="font-size:150%;color: blue;">' . $this->name . '</span>已通过审核,正在处理中!
                        详情请查看:<a href="http://58.246.226.254:9099/?#/v1/requirements/index">http://58.246.226.254:9099</a></p></div>';
            }else{
                $event->content = '<div>' .
                    $this->creator . '<p style=" text-indent:2em;">你好:</p>
                        <p style="text-indent:2em;">您的需求建议<span style="font-size:150%;color: blue;">' . $this->name . '</span>已经处理完成!
                        详情请查看:<a href="http://58.246.226.254:9099/?#/v1/requirements/index">http://58.246.226.254:9099</a></p></div>';
            }

            $this->trigger(self::EVENT_SEND_EMAIL, $event);
        }
    }
   */
}
