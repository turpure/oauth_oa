<?php
namespace mdm\admin\models\form;

use backend\models\AuthAssignment;
use mdm\admin\models\Department;
use mdm\admin\models\DepartmentChild;
use Yii;
use mdm\admin\models\User;
use mdm\admin\models\StoreChild;
use mdm\admin\models\PositionChild;
use yii\base\Model;
use yii\base\InvalidParamException;
use yii\helpers\ArrayHelper;
use backend\modules\v1\models\ApiCondition;
use mdm\admin\models\AdminAuthItem;
use mdm\admin\models\AdminAuthAssignment;


/**
 * update-user form
 */
class UpdateUser extends Model
{
    public $department;
    public $child_depart;
    public $position;
    public $store;
    public $mapPersons;
    public $mapWarehouse;
    public $mapPlat;
    public $role;
    public $user_id;
    public $username;
    private $_position;
    private $_store;
    private $_role;

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
        $user = User::findOne($userid);
        $mapPersons = explode(',',$user->mapPersons);
        $mapPlat = explode(',',$user->mapPlat);
        $mapWarehouse = explode(',',$user->mapWarehouse);
        $this->user_id = (int)$userid;
        $this->username = $user->username;
        $this->mapPersons = $mapPersons;
        $this->mapPlat = $mapPlat;
        $this->mapWarehouse = $mapWarehouse;
        $this->role = ArrayHelper::getColumn(AdminAuthAssignment::findAll(['user_id' =>$userid]),'item_name');
        $department = DepartmentChild::find()->where(['user_id'=>$userid])->one();
        if($department){
            $departInfo = Department::findOne($department['department_id']);
            $this->department = empty($departInfo->parent)?$departInfo['id']:$departInfo->parent;
            $this->child_depart = empty($departInfo['parent'])?0:$departInfo['id'];
        }else{
            $this->department = $this->child_depart = 0;
        }
        $this->_position = ArrayHelper::getColumn(ArrayHelper::toArray(PositionChild::find()->where(['user_id'=>$userid])->all()),'position_id');
        $this->_store = ArrayHelper::getColumn(ArrayHelper::toArray(StoreChild::find()->where(['user_id'=>$userid])->all()),'store_id');
        $this->_role = ArrayHelper::getColumn(ArrayHelper::toArray(AdminAuthAssignment::find()->where(['user_id'=>$userid])->all()),'item_name');
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
            [['department','child_depart'],'string'],
            [['department',],'required'],
            [['store','position','mapPersons','mapPlat','mapWarehouse','role'],'safe']
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
            $user = User::findOne($userid);
            $user->mapPersons = !empty($this->mapPersons)?implode(',',$this->mapPersons):null;
            $user->mapPlat = !empty($this->mapPlat)? implode(',',$this->mapPlat):null;
            $user->mapWarehouse = !empty($this->mapWarehouse) ?implode(',',$this->mapWarehouse):null;
            if(!$user->save()) {
               throw new \Exception('user保存失败！');
            }
            $this->position = !empty($this->position)?$this->position:[];
            $this->store = !empty($this->store)?$this->store:[];

            $DepartmentChild = DepartmentChild::find()->where(['user_id'=>$userid])->one();
            $DepartmentChild = $DepartmentChild?$DepartmentChild:new DepartmentChild();
            $Positon = new PositionChild();
            $DepartmentChild->user_id = $this->user_id;
            $DepartmentChild->department_id = $this->child_depart?:$this->department;


            // 增改删店铺
            StoreChild::deleteAll(['store_id'=>$this->store]);
            foreach ($this->store as $sto) {
                $child = new StoreChild();
                $child->user_id = $this->user_id;
                $child->store_id = $sto;
                $child->save();
            }

            //增改删角色
            foreach ($this->role as $roleName) {
                $role = AdminAuthAssignment::findOne(['user_id' =>$userid, 'item_name' => $roleName]);
                if ($role === null) {
                    $role = new AdminAuthAssignment();
                }
                $role->setAttributes(['item_name' =>$roleName, 'user_id' => $userid,'created_at' => time()]);
                if(!$role->save()) {
                    throw new \Exception('角色保存失败！');
                }
            }
            $diff_roles = \array_diff($this->_role, $this->role);
            foreach ($diff_roles as $diff_role) {
                $roles = AdminAuthAssignment::find()->where(['user_id'=>$userid,'item_name'=>$diff_role])->all();
                foreach ($roles as $role) {
                    $role->delete();
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
            'mapPersons' => '对应销售',
            'mapPlat' => '对应平台',
            'mapWarehouse' => '对应仓库',
            'role' => '角色',
        ];
    }

    /**
     *@brief 获取销售人员
     **/

    public static function getMapPersons()
    {
        $ret = ApiCondition::getUsers();
        $salers = array_values(array_filter($ret, function ($ele) {return $ele['position'] === '销售'; }));
        $name = ArrayHelper::getColumn($salers, 'username');
        return array_combine($name, $name);
    }

    /**
     * @brief 获取仓库
     * @return array
     */
    public static function getWarehouse()
    {
        $store = array_values(ApiCondition::getStore());
        return array_combine($store, $store);
    }

    /**
     * @brief 获取所有平台
     * @return array
     */
    public static function getMapPlat()
    {
        $ret = ApiCondition::getUserPlat();
        $plat = array_values(ArrayHelper::getColumn($ret, 'plat'));
        return array_combine($plat, $plat);
    }

    /**
     * @brief 获取所有角色
     * @return array
     */
    public static function getRole()
    {
        $ret = AdminAuthItem::findAll(['type' => 1]);
        $role = ArrayHelper::getColumn($ret,'name');
        return array_combine($role, $role);
    }

}
