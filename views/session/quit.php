<?php
/**
 * Page handlinh session end
 *
 * @var k7zz\humhub\bbb\models\forms\SessionForm $session
 */
use yii\helpers\Html;
?>

<div class="content">
    <div id="layout-content">
        <div class="container-fluid">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h1>Action handles response. Quit view unused <?= Html::encode($session->title) ?></h1>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$this->registerJs("
    if (top != self) {
        top.location.href = '/bbb/sessions?highlight=" . rawurlencode($session->id) . "'; 
  }
    else
        window.close();    
", \yii\web\View::POS_HEAD);
?>