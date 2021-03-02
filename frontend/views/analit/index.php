<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel frontend\models\search\AnalitSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Track pages';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="analit-index">

    
<div class="card">
              <div class="card-header border-transparent">
                <h3 class="card-title">Track pages user# <?php echo $_GET['id'];?></h3>

              </div>
              <!-- /.card-header -->
              <div class="card-body p-0">
                <div class="table-responsive">
                  <table class="table m-0">
                    <thead>
                        <?php
                        ?>
                    <tr>
                      <th>Page</th>
                      <th>Time</th>
                      <th>Leave</th>
                    </tr>
                    </thead>
                    <tbody>
                  <?php
                  foreach ($track as $key => $value) {
                    if ($value["t_lev"]) {
                        $leave = $value["t_lev"];
                    } else {
                        $leave = 0;
                    }
                    echo '
                    <tr>
                      <td><span class="badge badge-success">'.$value["page"].'</span></td>
                      <td>'.gmdate("H:i:s", $value["t_dur"]).'</td>
                      <td>'.$leave.'</td>
                      <td>
                    </tr>';
                  }
                  ?>
                   
                    </tbody>
                  </table>
                </div>
                <!-- /.table-responsive -->
              </div>
              <!-- /.card-body -->
              <div class="card-footer clearfix">
                <a href="/users/index" class="btnindex btn-sm btn-info float-left">Back</a>
              </div>
              <!-- /.card-footer -->
            </div>

</div>
