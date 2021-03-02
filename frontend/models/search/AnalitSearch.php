<?php

namespace frontend\models\search;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Analit;

/**
 * AnalitSearch represents the model behind the search form of `common\models\Analit`.
 */
class AnalitSearch extends Analit
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'user_id', 'leave'], 'integer'],
            [['page', 'time', 'time_start', 'time_end', 'during'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
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
        $query = Analit::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
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
            'user_id' => $this->user_id,
            'leave' => $this->leave,
        ]);

        $query->andFilterWhere(['like', 'page', $this->page])
            ->andFilterWhere(['like', 'time', $this->time])
            ->andFilterWhere(['like', 'time_start', $this->time_start])
            ->andFilterWhere(['like', 'time_end', $this->time_end])
            ->andFilterWhere(['like', 'during', $this->during]);

        return $dataProvider;
    }
}
