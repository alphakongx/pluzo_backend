<?php

namespace frontend\controllers;

use Yii;
use common\models\Client;
use frontend\models\search\UserSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

use frontend\models\Chat;
use frontend\models\Party;
use frontend\models\Message;
use common\models\User;

/**
 * UsersController implements the CRUD actions for User model.
 */
class UsersController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all User models.
     * @return mixed
     */
    public function actionIndex()
    {   
        $searchModel = new UserSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionChat($id)
    {   
        $model = new Message();
        $chat_id = Message::getChat($id);

        if ($model->load(Yii::$app->request->post())) {
            if(isset($chat_id)){
                Message::addMessage($model->text, $chat_id);
                return $this->redirect(['chat', 'id' => $id]);
            } else {
                echo 'error chat_id';die();
            }
        }

        return $this->render('chat', [
            'model' => $model,
            'client' => Client::find()->where(['id'=>$id])->one(),
            'id' => $id,
            'message' => Message::find()->where(['chat_id'=>$chat_id])->all(),
        ]);
    }

    /**
     * Displays a single User model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {   
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new User model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Client();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing User model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {   
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            if($model->status == Client::USER_BANNED){
                Client::banUser($model->id);
            }
            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing User model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        $party = Party::find()->where(['user_id'=>$id])->all();
        if($party){
            foreach ($party as $key => $value) {
                \Yii::$app
                ->db
                ->createCommand()
                ->delete('message', ['chat_id' => $value['chat_id']])
                ->execute();

                \Yii::$app
                ->db
                ->createCommand()
                ->delete('chat', ['id' => $value['chat_id']])
                ->execute();

                \Yii::$app
                ->db
                ->createCommand()
                ->delete('party', ['chat_id' => $value['chat_id']])
                ->execute();
            }
        }

        \Yii::$app
            ->db
            ->createCommand()
            ->delete('client', ['id' => $id])
            ->execute();

        \Yii::$app
            ->db
            ->createCommand()
            ->delete('badge', ['user_id' => $id])
            ->execute();

        \Yii::$app
            ->db
            ->createCommand()
            ->delete('token', ['user_id' => $id])
            ->execute();

        \Yii::$app
            ->db
            ->createCommand()
            ->delete('images', ['user_id' => $id])
            ->execute();

        \Yii::$app
            ->db
            ->createCommand()
            ->delete('chat', ['user_id' => $id])
            ->execute();

        \Yii::$app
            ->db
            ->createCommand()
            ->delete('friend', ['user_source_id' => $id])
            ->execute();

        \Yii::$app
            ->db
            ->createCommand()
            ->delete('friend', ['user_target_id' => $id])
            ->execute();

        \Yii::$app
            ->db
            ->createCommand()
            ->delete('like', ['user_source_id' => $id])
            ->execute();

        \Yii::$app
            ->db
            ->createCommand()
            ->delete('like', ['user_target_id' => $id])
            ->execute();

        \Yii::$app
            ->db
            ->createCommand()
            ->delete('message', ['user_id' => $id])
            ->execute();

        
        \Yii::$app
            ->db
            ->createCommand()
            ->delete('party', ['user_id' => $id])
            ->execute();

        \Yii::$app
            ->db
            ->createCommand()
            ->delete('stream', ['user_id' => $id])
            ->execute();

        \Yii::$app
            ->db
            ->createCommand()
            ->delete('stream_user', ['user_id' => $id])
            ->execute();


        return $this->redirect(['index']);
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Client::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
