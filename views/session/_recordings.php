<?php
/**
 * Widget view: List of BBB session recordings.
 *
 * @var array $recordings 
 * @var bool $canAdminister */

use yii\helpers\Html;

?>

<?php
if (empty($recordings)) {
    echo Html::tag('p', Yii::t('BbbModule.base', 'No recordings available'), ['class' => 'text-muted']);
    return;
}

foreach ($recordings as $rec) {
    echo $this->render('_recordingItem', [
        'rec' => $rec,
        'canAdminister' => $canAdminister,
    ]);
} ?>