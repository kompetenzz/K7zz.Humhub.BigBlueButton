<?php

namespace k7zz\humhub\bbb\models;

use yii\db\ActiveRecord;

/**
 * Tracks per-format publish visibility for BBB recordings.
 *
 * A row with published=1 means the format is visible to non-admins.
 * Missing row or published=0 means unpublished (default).
 *
 * @property string $record_id
 * @property string $format_type
 * @property int    $published
 */
class RecordingFormat extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'bbb_recording_format';
    }

    public static function isPublished(string $recordId, string $formatType): bool
    {
        return (bool) static::find()
            ->where(['record_id' => $recordId, 'format_type' => $formatType, 'published' => 1])
            ->exists();
    }

    public static function setPublished(string $recordId, string $formatType, bool $publish): bool
    {
        $model = static::findOne(['record_id' => $recordId, 'format_type' => $formatType]);
        if (!$model) {
            $model = new static();
            $model->record_id   = $recordId;
            $model->format_type = $formatType;
        }
        $model->published = $publish ? 1 : 0;
        return $model->save();
    }
}
