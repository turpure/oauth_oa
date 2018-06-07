<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-05-26 14:01
 */

namespace mdm\admin\models;
use mdm\admin\components\DbManager;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;


class Department extends ActiveRecord
{

    public function behaviors()
    {
        return array_merge(parent::behaviors(),
            [TimestampBehavior::className()]
            ); // TODO: Change the autogenerated stub
    }


    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return DbManager::$departmentTable;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['department'], 'required'],
            [['id','parent'], 'integer'],
            [['department','description'], 'string'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '部门编号',
            'parent' => '上级部门',
            'department' => '部门名称',
            'description' => '描述',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }

    /**
     * 可用列表
     *
     * @return array|\yii\db\ActiveRecord[]
     */
    public function lists()
    {
        $model = clone $this;
        $query = $model->find();
        $query->select(['id', 'department_name']);
        $query->andWhere(['group_status' => self::STATUS_OPEN]);
        $result = $query->asArray()->all();

        return $result;
    }

    /**
     * 分配添加
     *
     * @param $id
     *
     * @return bool
     */
    public function assign()
    {
        $data = Yii::$app->getRequest()->post();
        foreach ($data as $val) {
            try {
                $group_child_model = new AuthGroupsChild();
                $group_child_model->add($this->id, $val);
            } catch (\Exception $exc) {
                Yii::error($exc->getMessage(), __METHOD__);
            }
        }
        Helper::invalidate();

        return true;
    }

    /**
     * 分配删除
     *
     * @param $id
     *
     * @return bool
     */
    public function revoke()
    {
        $data = Yii::$app->getRequest()->post();
        foreach ($data as $val) {
            try {
                $group_child_model = new AuthGroupsChild();
                $group_child_model->remove($this->id, $val);
            } catch (\Exception $exc) {
                Yii::error($exc->getMessage(), __METHOD__);
            }
        }
        Helper::invalidate();

        return true;
    }

    /**
     * 分配用户列表
     *
     * @return array
     */
    public function assignUser()
    {
        $group_child_model = new AuthGroupsChild();
        //assign 已分配的
        $assign = $group_child_model->assigned($this->id);
        $user_id_arr = array_filter(array_column($assign, 'id'));
        //all  所有
        $user_model = new \wind\rest\models\searchs\User();
        $all = $user_model->allUsers(['id', 'realname']);
        foreach ($all as $key => $val) {
            if (in_array($val['id'], $user_id_arr)) {
                unset($all[$key]);
            }
        }
        sort($all);

        return compact(['all', 'assign']);
    }


}