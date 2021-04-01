<?php

namespace frontend\models\search;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use common\models\Client;
use Yii;
use yii\db\Expression;

/**
 * UserSearch represents the model behind the search form of `common\models\User`.
 */
class UserSearch extends Client
{   

    //public $count_friend;
    //public $count_swipes;
    //public $likes;
    //public $dis;


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'status',  'updated_at', 'logged_at', 'gender'], 'integer'],
            [['created_at', 'username', 'address', 'auth_key', 'access_token', 'password_hash', 'oauth_client', 'oauth_client_user_id', 'email', 'first_name', 'last_name', 'phone', 'image', 'forgot_sms_code', 'forgot_sms_code_exp', 'login_sms_code', 'login_sms_code_exp', 'reset_pass_code', 'verify_sms_code', 'birthday', 'count_friend', 'count_swipes', 'id', 'premium', 'likes', 'dis', 'rait'], 'safe'],
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

        /*$query = Client::find()->select(new Expression("*, (SELECT COUNT(*) as `count_friend` FROM `friend` l1 
            INNER JOIN `friend` l2 ON l1.user_source_id = l2.user_target_id AND l2.user_source_id = l1.user_target_id
            WHERE l1.user_source_id = `client`.`id`) AS `count_friend`, (SELECT COUNT(*) as `count_swipes` FROM `like` WHERE `user_source_id`= `client`.`id`) AS `count_swipes`, (SELECT COUNT(*) as `likes` FROM `like` WHERE `like`.`user_target_id`= `client`.`id` AND `like`.`like` IN ('1','2')) AS `likes`, (SELECT COUNT(*) as `dis` FROM `like` WHERE `like`.`user_target_id`= `client`.`id` AND `like`.`like` IN ('0')) AS `dis`, ((SELECT COUNT(*) as `likes` FROM `like` WHERE `like`.`user_target_id`= `client`.`id` AND `like`.`like` IN ('1','2'))/((SELECT COUNT(*) as `likes` FROM `like` WHERE `like`.`user_target_id`= `client`.`id` AND `like`.`like` IN ('1','2'))+(SELECT COUNT(*) as `dis` FROM `like` WHERE `like`.`user_target_id`= `client`.`id` AND `like`.`like` IN ('0')))) as `rait`"));*/

        $query = Client::find()->select(new Expression("*, 
            (SELECT COUNT(*) as `count_friend` FROM `friend` l1 
            INNER JOIN `friend` l2 ON l1.user_source_id = l2.user_target_id AND l2.user_source_id = l1.user_target_id
            WHERE l1.user_source_id = `client`.`id`) AS `count_friend`, 
            (SELECT COUNT(*) as `count_swipes` FROM `like` WHERE `user_source_id`= `client`.`id`) AS `count_swipes`, 
            (SELECT COUNT(*) as `likes` FROM `like` WHERE `like`.`user_target_id`= `client`.`id` AND `like`.`like` IN ('1','2')) AS `likes`, 
            (SELECT COUNT(*) as `dis` FROM `like` WHERE `like`.`user_target_id`= `client`.`id` AND `like`.`like` IN ('0')) AS `dis`, 
            (SELECT COUNT(*) as `track` FROM `analit` WHERE `analit`.`user_id`= `client`.`id` ) AS `analit`, 
            0 as `rait`"));

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $dataProvider->setSort([
            'defaultOrder' => ['created_at' => SORT_DESC],
            'attributes' => [
                'id',
                'created_at',
                'username',
                'first_name',
                'gender',
                'address',
                'birthday',
                'phone',
                'premium',
                'count_friend' => [
                    'asc' => ['count_friend' =>SORT_ASC ],
                    'desc' => ['count_friend' => SORT_DESC],
                    'default' => SORT_ASC
                ],   
                'count_swipes' => [
                    'asc' => ['count_swipes' =>SORT_ASC ],
                    'desc' => ['count_swipes' => SORT_DESC],
                    'default' => SORT_ASC
                ],  
                'likes' => [
                    'asc' => ['likes' =>SORT_ASC ],
                    'desc' => ['likes' => SORT_DESC],
                    'default' => SORT_ASC
                ],   
                'dis' => [
                    'asc' => ['dis' =>SORT_ASC ],
                    'desc' => ['dis' => SORT_DESC],
                    'default' => SORT_ASC
                ],   
                'rait' => [
                    'asc' => ['rait' =>SORT_ASC ],
                    'desc' => ['rait' => SORT_DESC],
                    'default' => SORT_ASC
                ],
                
                   
            ]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'logged_at' => $this->logged_at,
            'gender' => $this->gender,
            'premium' => $this->premium,
        ]);

        if(strlen($this->likes) > 0){
            $query->having(['likes' => $this->likes]);
        }

        if(strlen($this->dis) > 0){
            $query->having(['dis' => $this->dis]);
        }

        if(strlen($this->count_friend) > 0){
            $query->having(['count_friend' => $this->count_friend]);
        }

        if(strlen($this->count_swipes) > 0){
            $query->having(['count_swipes' => $this->count_swipes]);
        }

        $query->andFilterWhere(['like', 'username', $this->username])
            ->andFilterWhere(['like', 'auth_key', $this->auth_key])
            ->andFilterWhere(['like', 'access_token', $this->access_token])
            ->andFilterWhere(['like', 'password_hash', $this->password_hash])
            ->andFilterWhere(['like', 'oauth_client', $this->oauth_client])
            ->andFilterWhere(['like', 'address', $this->address])
            ->andFilterWhere(['like', 'oauth_client_user_id', $this->oauth_client_user_id])
            ->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'first_name', $this->first_name])
            ->andFilterWhere(['like', 'last_name', $this->last_name])
            ->andFilterWhere(['like', 'phone', $this->phone])
            ->andFilterWhere(['like', 'image', $this->image])
            ->andFilterWhere(['like', 'forgot_sms_code', $this->forgot_sms_code])
            ->andFilterWhere(['like', 'forgot_sms_code_exp', $this->forgot_sms_code_exp])
            ->andFilterWhere(['like', 'login_sms_code', $this->login_sms_code])
            ->andFilterWhere(['like', 'login_sms_code_exp', $this->login_sms_code_exp])
            ->andFilterWhere(['like', 'reset_pass_code', $this->reset_pass_code])
            ->andFilterWhere(['like', 'verify_sms_code', $this->verify_sms_code])
            ->andFilterWhere(['like', 'birthday', $this->birthday]);

        return $dataProvider;
    }
    
}
