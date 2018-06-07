<?php
namespace mdm\admin\models\form;

use mdm\admin\models\DepartmentChild;
use Yii;
use mdm\admin\models\User;
use mdm\admin\models\StoreChild;
use mdm\admin\models\PositionChild;
use yii\base\Model;
use yii\base\InvalidParamException;
use yii\helpers\ArrayHelper;
/**
 * update-user form
 */
class UpdateUser extends Model
{
    public $department;
    public $position;
    public $store;
    public $user_id;
    public $username;
    private $_position;
    private $_store;

    /**
     * Creates a form model given a token.
     *
     * @param  string $token
     * @param  array $config name-value pairs that will be used to initialize the object properties
     * @throws \yii\base\InvalidParamException if token is empty or not valid
     */
    public function __construct($userid, $config = [])
    {
        if (empty($userid)) {
            throw new InvalidParamException('user id cannot be blank.');
        }
        $this->user_id = (int)$userid;
        $this->username = User::findOne($userid)->username;
        $department = DepartmentChild::find()->where(['user_id'=>$userid])->one();
        $this->department = $department?$department:'';
        $this->_position = ArrayHelper::getColumn(ArrayHelper::toArray(PositionChild::find()->where(['user_id'=>$userid])->all()),'position_id');
        $this->_store = ArrayHelper::getColumn(ArrayHelper::toArray(StoreChild::find()->where(['user_id'=>$userid])->all()),'store_id');
        $this->position = $this->_position;
        $this->store = $this->_store;
        if (!$this->username) {
            throw new InvalidParamException('cannot find user name');
        }
        parent::__construct($config);
    }
    /**
     *
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id'],'integer'],
            [['department',],'string'],
            [['department',],'required'],
            [['store','position'],'safe']
        ];
    }

    /**
     * Signs user up.
     *
     * @return User|null the saved model or null if saving fails
     */
    public function save()
    {
        if ($this->validate()) {

            $userid = $this->user_id;
            $this->position = !empty($this->position)?$this->position:[];
            $this->store = !empty($this->store)?$this->store:[];

            $DepartmentChild = DepartmentChild::find()->where(['user_id'=>$userid])->one();
            $DepartmentChild = $DepartmentChild?$DepartmentChild:new DepartmentChild();
            $Positon = new PositionChild();
            $DepartmentChild->user_id = $this->user_id;
            $DepartmentChild->department_id = $this->department;


            // 增改删店铺
            foreach ($this->store as $sto) {
                $child = StoreChild::find()->where(['user_id'=>$userid,'store_id'=>$sto])->one();
                $child = $child?$child:new StoreChild();
                $child->user_id = $this->user_id;
                $child->store_id = $sto;
                $child->save();
            }
            $diff_stores = \array_diff($this->_store, $this->store);
            foreach ($diff_stores as $diff_sto) {
                $stores = StoreChild::find()->where(['user_id'=>$userid,'store_id'=>$diff_sto])->all();
                foreach ($stores as $sto) {
                    $sto->delete();
                }
            }


            // 增改删职位
            foreach ($this->position as $pos) {
                $child = PositionChild::find()->where(['user_id'=>$userid,'position_id'=>$pos])->one();
                $child = $child?$child:clone $Positon;
                $child->position_id = $pos;
                $child->user_id = $this->user_id;
                $child->save();
            }
            $diff_positions = \array_diff($this->_position, $this->position);
            foreach ($diff_positions as $diff_pos) {
                $positons = PositionChild::find()->where(['user_id'=>$userid,'position_id'=>$diff_pos])->all();
                foreach ($positons as $pos) {
                    $pos->delete();
                }
            }
        }


        if($DepartmentChild->save()) {
            return True;
        }


        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'department' => '部门',
            'store' => '店铺',
            'position' => '职位',
        ];
    }
}
