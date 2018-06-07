<?php
namespace mdm\admin\models\form;

use mdm\admin\models\PositionMenu;
use mdm\admin\models\Position;
use Yii;
use yii\base\Model;
use yii\base\InvalidParamException;
use yii\helpers\ArrayHelper;
/**
 * update-user form
 */
class UpdatePosition extends Model
{
    public $position_id;
    public $position;
    public $menu;
    private $_menu;

    /**
     * Creates a form model given a token.
     *
     * @param  string $token
     * @param  array $config name-value pairs that will be used to initialize the object properties
     * @throws \yii\base\InvalidParamException if token is empty or not valid
     */
    public function __construct($position_id, $config = [])
    {
        if (empty($position_id)) {
            throw new InvalidParamException('position id cannot be blank.');
        }
        $this->position_id = (int)$position_id;
        $this->position = Position::findOne($position_id)->position;
        $menu = ArrayHelper::getColumn(ArrayHelper::toArray(PositionMenu::find()->where(['position_id'=>$position_id])->all()),'menu_id');
        $this->menu = $menu?$menu:'';
        $this->_menu = $menu?$menu:'';
        if (!$this->position) {
            throw new InvalidParamException('cannot find position name');
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
            [['position_id'],'integer'],
            [['menu',],'safe'],
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

            $position_id = $this->position_id;
            $this->position = !empty($this->position)?$this->position:[];

            // 增改删店铺
            foreach ($this->menu as $mn) {
                $Menu = PositionMenu::find()->where(['position_id'=>$position_id,'menu_id'=>$mn])->one();
                $Menu = $Menu?$Menu:new PositionMenu();
                $Menu->position_id = $this->position_id;
                $Menu->menu_id = $mn;
                $Menu->save();
            }
            $diff_menus = \array_diff($this->_menu, $this->menu);
            foreach ($diff_menus as $diff_mn) {
                $Menu = PositionMenu::find()->where(['position_id'=>$position_id,'menu_id'=>$diff_mn])->all();
                foreach ($Menu as $menu) {
                    $menu->delete();
                }
            }
        }
        return True;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'menu' => '职位',
        ];
    }
}
