<?php

namespace frontend\models\search;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use frontend\models\Message;

/**
 * MessageSearch represents the model behind the search form of `frontend\models\Message`.
 */
class MessageSearch extends Message
{   
    public $find_user;
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'chat_id', 'user_id', 'status'], 'safe'],
            [['text', 'image', 'created_at', 'find_user'], 'safe'],
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
        $query = Message::find()->joinwith(['user'])->where(['type'=>'message'])->andwhere(['!=', 'user_id', 0])->orderby(['created_at'=>SORT_DESC])->groupBy(['chat_id']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        if (isset($this->find_user)) {
            $query->andFilterWhere([
                'client.id' => $this->find_user
            ]);
        }
        
        if ($this->chat_id) {
            $query->andFilterWhere(['or',
            ['like','username',$this->chat_id],
            ['like','chat_id',$this->chat_id]]);
        }
        
        return $dataProvider;
    }
}
