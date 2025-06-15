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
                    <h1>Quit page unused <?= Html::encode($session->title) ?></h1>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
/* JS, um den User-Picker live ein- und auszublenden */
$this->registerJs("
    if (top != self) top.location.href = location.href;    
", \yii\web\View::POS_HEAD);
?>