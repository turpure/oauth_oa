<?php

namespace mdm\admin\models\searchs;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use mdm\admin\models\User as UserModel;

/**
 * User represents the model behind the search form about `mdm\admin\models\User`.
 */
class User extends UserModel
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'status', 'created_at', 'updated_at'], 'integer'],
            [['username', 'auth_key', 'password_hash', 'password_reset_token', 'email','depart',
                'position','role','mapPersons','mapWarehouse','mapPlat','canStockUp'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = UserModel::find();




        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);
        if (!$this->validate()) {
            $query->where('1=0');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);
        if($this->depart){
            $query->leftJoin('auth_department_child dc','dc.user_id=user.id')
                ->leftJoin('auth_department d','d.id=dc.department_id')
                ->andFilterWhere(['like', 'department', $this->depart]);
        }
        if($this->position){
            $query->leftJoin('auth_position_child pc','pc.user_id=user.id')
                ->leftJoin('auth_position p','p.id=pc.position_id')
                ->andFilterWhere(['like', 'position', $this->position]);
        }
        if($this->role) {
            $query->leftJoin('auth_assignment ag','ag.user_id=user.id')
                ->andFilterWhere(['like','item_name',$this->role]);
        }
        $query->andFilterWhere(['like', 'username', $this->username])
            ->andFilterWhere(['like', 'auth_key', $this->auth_key])
            ->andFilterWhere(['like', 'mapPersons', $this->mapPersons])
            ->andFilterWhere(['like', 'mapPlat', $this->mapPlat])
            ->andFilterWhere(['like', 'mapWarehouse', $this->mapWarehouse])
            ->andFilterWhere(['=', 'canStockUp', $this->canStockUp])
            ->andFilterWhere(['like', 'password_hash', $this->password_hash])
            ->andFilterWhere(['like', 'password_reset_token', $this->password_reset_token])
            ->andFilterWhere(['like', 'email', $this->email]);

        return $dataProvider;
    }
    public function allUsers($select = '*')
    {
        $query = UserModel::find();
        $query->select($select);
        $query->andWhere(['status' => parent::STATUS_ACTIVE]);
        $result = $query->asArray()->all();

        return $result;
    }
}
