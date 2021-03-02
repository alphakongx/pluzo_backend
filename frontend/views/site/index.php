<?php
/**
 * @var yii\web\View $this
 */
$this->title = Yii::$app->name;
use miloschuman\highcharts\Highcharts;
use api\models\Like;
use common\models\Client;
use frontend\models\Payment;

$total_swipes = Like::find()->count();
$total_users = Client::find()->count();
$total_payments = Payment::find()->count();
?>
<div class="site-index">
    <div class="container">
    
<div class="row">
          <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box">
              <span class="info-box-icon bg-info elevation-1"><i class="fas fa-cog"></i></span>

              <div class="info-box-content">
                <span class="info-box-text">TOTAL usage time</span>
                <span class="info-box-number">
                  0
                  <small>hrs</small>
                </span>
              </div>
              <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
          </div>
          <!-- /.col -->
          <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box mb-3">
              <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-thumbs-up"></i></span>

              <div class="info-box-content">
                <span class="info-box-text">Swipes</span>
                <span class="info-box-number"><?php echo $total_swipes;?></span>
              </div>
              <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
          </div>
          <!-- /.col -->

          <!-- fix for small devices only -->
          <div class="clearfix hidden-md-up"></div>

          <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box mb-3">
              <span class="info-box-icon bg-success elevation-1"><i class="fas fa-shopping-cart"></i></span>

              <div class="info-box-content">
                <span class="info-box-text">Payments</span>
                <span class="info-box-number"><?php echo $total_payments;?></span>
              </div>
              <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
          </div>
          <!-- /.col -->
          <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box mb-3">
              <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-users"></i></span>

              <div class="info-box-content">
                <span class="info-box-text">Users</span>
                <span class="info-box-number"><?php echo $total_users;?></span>
              </div>
              <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
          </div>
          <!-- /.col -->
        </div>


    <?php

$connection = Yii::$app->getDb();
$command = $connection->createCommand("SELECT `created_at`, from_unixtime(`created_at`, '%Y %M') as `date`, COUNT(*) as `count` FROM `client` GROUP BY `date` ORDER BY `created_at` ASC");
$users = $command->queryAll();


$date = [];
$count = [];
foreach($users as $d){
  array_push($date, $d['date']);
  array_push($count, (int) $d['count']);
}

echo '<h2>Users<h2>';
echo '<hr>';
echo Highcharts::widget([
   'options' => [
      'title' => ['text' => 'Users'],
      'xAxis'=> [
          'categories'=> $date,
      ],
      'yAxis' => [
         'title' => ['text' => 'All users']
      ],
      'series' => [
         ['color'=> '#0066FF', 'name' => 'Users', 'data' => $count],
      ],
      'legend'=> [
          'layout'=> 'vertical',
          'align'=> 'right',
          'verticalAlign'=> 'middle'
      ],
      'responsive'=> [
        'rules'=> [[
            'condition'=> [
                'maxWidth'=> 500
            ],
            'chartOptions'=> [
                'legend'=> [
                    'layout'=> 'horizontal',
                    'align'=> 'center',
                    'verticalAlign'=> 'bottom'
                ]
            ]
        ]]
      ],
   ]
]);
    ?>

    </div>
</div>
