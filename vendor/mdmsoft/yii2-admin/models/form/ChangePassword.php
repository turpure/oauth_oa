<?php

namespace mdm\admin\models\form;

use Yii;
use mdm\admin\models\User;
use yii\base\Model;

/**
 * Description of ChangePassword
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class ChangePassword extends Model
{
    //public $oldPassword;
    public $newPassword;
    public $retypePassword;

    public function attributeLabels()
    {
        return [
            'newPassword' => '新密码',
            'retypePassword' => '重复新密码',
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            //[['oldPassword', 'newPassword', 'retypePassword'], 'required'],
            [['newPassword', 'retypePassword'], 'required'],
            //[['oldPassword'], 'validatePassword'],
            [['newPassword'], 'string', 'min' => 6],
            [['retypePassword'], 'compare', 'compareAttribute' => 'newPassword'],
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     */
    public function validatePassword()
    {
        /* @var $user User */
        $user = Yii::$app->user->identity;
        if (!$user || !$user->validatePassword($this->oldPassword)) {
            $this->addError('oldPassword', 'Incorrect old password.');
        }
    }

    /**
     * Change password.
     *
     * @return User|null the saved model or null if saving fails
     */
    public function change($id)
    {
        if ($this->validate()) {
            /* @var $user User */
            //$user = Yii::$app->user->identity;
            $user = User::findOne($id);
            $user->password_reset_token = $this->newPassword;
            $user->setPassword($this->newPassword);
            $user->generateAuthKey();
            if ($user->save()) {
                return true;
            }
        }

        return false;
    }
}
