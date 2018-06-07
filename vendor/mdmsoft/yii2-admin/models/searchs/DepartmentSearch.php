<?php
/**
 * @desc PhpStorm.
 * @author: Administrator
 * @since: 2018-05-26 14:19
 */

namespace mdm\admin\models\searchs;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use mdm\admin\models\Department;

class DepartmentSearch extends Department
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
            [['department','description'], 'string'],
            [['created_at','updated_at'], 'integer'],
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
        $query = Department::find();

        // add conditions that should always apply here

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

        $query->andFilterWhere(['like', 'department', $this->department]);

        $query->orderBy('id');

        return $dataProvider;
    }
}