<?php

namespace mdm\admin\models;

use mdm\admin\models\DepartmentChild;
use mdm\admin\components\Configs;
use mdm\admin\components\DbManager;
use mdm\admin\components\Helper;
use Yii;
use yii\base\Object;

/**
 * Description of Assignment
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 2.5
 */
class Dispatch extends Object
{
    /**
     * @var integer User id
     */
    public $id;
    /**
     * @var \yii\web\IdentityInterface User
     */
    public $department;

    /**
     * @inheritdoc
     */
    public function __construct($id, $department = null, $config = array())
    {
        $this->id = $id;
        $this->department = $department;
        parent::__construct($config);
    }

    /**
     * Grands a roles from a department.
     )* @param array $items
     * @return integer number of successful grand
     */
    public function assign($items)
    {
        $data = Yii::$app->getRequest()->post();
        foreach ($items as $val) {
            try {
                $department_child_model = new DepartmentChild();
                $user_id = \mdm\admin\models\User::findOne(['username'=> $val])->id;
                $department_child_model->add((int)$this->id, $user_id);
            } catch (\Exception $exc) {
                Yii::error($exc->getMessage(), __METHOD__);
            }
        }
        Helper::invalidate();

        return true;
    }

    /**
     * Revokes a roles from a department.
     * @param array $items
     * @return integer number of successful revoke
     */
    public function revoke($items)
    {
        foreach ($items as $val) {
            try {
                $department_child_model = new DepartmentChild();
                $user_id = \mdm\admin\models\User::findOne(['username'=> $val])->id;
                $department_child_model->remove($this->id, $user_id);
            } catch (\Exception $exc) {
                Yii::error($exc->getMessage(), __METHOD__);
            }
        }
        Helper::invalidate();

        return true;
    }

    /**
     * Get all available and assigned roles/permission
     * @return array
     */
    /**
     * 分配用户列表
     *
     * @return array
     */
    public function getUser()
    {
        $department_child_model = new DepartmentChild();

        //assigned 已分配的
        $assigned = [];
        $assign = $department_child_model->assigned($this->id);
        foreach ($assign as $ele) {
            $assigned[$ele['username']] = 'role';
        }

        //available 可分配的
        $available = [];

        $user_id_arr = array_filter(array_column($assign, 'id'));

        $user_model = new \mdm\admin\models\searchs\User();
        $all = $user_model->allUsers(['id', 'username']);
        foreach ($all as $key => $val) {
            if (!in_array($val['id'], $user_id_arr)) {
                $available[$val['username']] = 'role';
            }

        }
        sort($all);
        return [
            'available' => $available,
            'assigned' => $assigned,
        ];

    }

    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        if ($this->department) {
            return $this->department->$name;
        }
    }
}
