<?php
/**
 * @var k7zz\humhub\bbb\models\JoinInfo $joinInfo  Vollständige Join-URL
 * @var string $title  Titel der Sessuin
 */

use yii\helpers\Html;
$raw = true;
$iframe = '<iframe src="' . Html::encode($joinInfo->url) . '" allow="fullscreen; camera *; microphone *, display-capture *" allowfullscreen style="border:0;width:100%;height:80vh;"></iframe>';
?>
<div class="content">
    <?php if ($raw) { ?>
        <?= $iframe; ?>
    <?php } else { ?>
        <div id="layout-content">
            <div class="container-fluid">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <strong><?= Html::encode($joinInfo->title) ?></strong>
                    </div>

                    <div class="panel-body" style="padding:0">
                        <?= $iframe; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>
</div>