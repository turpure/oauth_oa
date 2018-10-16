<?php
/**
 * @desc PhpStorm.
 * @author: Administrator
 * @since: 2018-05-26 14:19
 */

namespace mdm\admin\models\searchs;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use mdm\admin\models\Store;

class StoreSearch extends Store
{
    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function rules()
    {
        return [
            [['store','platform'], 'string'],
            [['username'], 'safe'],

        ];
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
        $query = Store::find()->select('auth_store.*,u.username')
            ->join('INNER JOIN','auth_store_child sc','sc.store_id=auth_store.id')
            ->join('INNER JOIN','user u','u.id=sc.user_id');

        // add conditions that should always apply here
        //var_dump($query);exit;
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => isset($params['pageSize']) && $params['pageSize'] ? $params['pageSize'] : 20,
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
        ]);

        $query->andFilterWhere(['like', 'store', $this->store]);
        $query->andFilterWhere(['like', 'username', $this->username]);

        return $dataProvider;
    }
}