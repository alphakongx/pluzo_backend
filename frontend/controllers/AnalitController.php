<?php

namespace frontend\controllers;

use Yii;
use common\models\Analit;
use frontend\models\search\AnalitSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * AnalitController implements the CRUD actions for Analit model.
 */
class AnalitController extends Controller
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
     * Lists all Analit models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new AnalitSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        if (!$_GET['id']) {
            die();
        }
        $data=Analit::find()
        ->select('distinct(`page`),sum(`during`) as `t_dur`,sum(`leave`) as `t_lev`')
        ->where(['user_id'=>$_GET['id']])
        ->groupBy('page')
        ->asArray()->all();

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'track'=>$data
        ]);
    }

    /**
     * Displays a single Analit model.
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
     * Deletes an existing Analit model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Analit model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Analit the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Analit::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
