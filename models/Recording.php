<?php
namespace k7zz\humhub\bbb\models;

use BigBlueButton\Core\PlaybackFormat;
use BigBlueButton\Core\Record;
use Yii;
class Recording
{
    private $record;
    private $format;
    private $startStamp;
    private $endStamp;

    public function __construct(Record $record)
    {
        $this->record = $record;
    }

    public function getRecord(): Record
    {
        return $this->record;
    }

    public function getFormat(): PlaybackFormat|null
    {
        //        Yii::error($this->record->getPlaybackFormats());
        if ($this->format === null) {
            $this->format = $this->record->getPlaybackFormats()[0] ?? null;
        }
        return $this->format;
    }

    public function getUrl(): ?string
    {
        return $this->getFormat()?->getUrl();
    }

    public function getDate(): string
    {
        if ($this->startStamp === null) {
            $this->startStamp = intval($this->record->getStartTime() / 1000);
        }
        return Yii::$app->formatter->asDate($this->startStamp);
    }

    public function getEnd(): string
    {
        if ($this->endStamp === null) {
            $this->endStamp = intval($this->record->getEndTime() / 1000);
        }
        return Yii::$app->formatter->asDate($this->endStamp);
    }

    public function getTime(): string
    {
        if ($this->startStamp === null) {
            $this->startStamp = intval($this->record->getStartTime() / 1000);
        }
        return Yii::$app->formatter->asTime($this->startStamp, "HH:mm");
    }

    public function getDuration(): string
    {
        if ($this->startStamp === null) {
            $this->startStamp = intval($this->record->getStartTime() / 1000);
        }
        if ($this->endStamp === null) {
            $this->endStamp = intval($this->record->getEndTime() / 1000);
        }
        return gmdate("H:i:s", $this->endStamp - $this->startStamp);
    }

    public function getImagePreviews(): array
    {
        return $this->getFormat()?->getImagePreviews() ?? [];
    }

    public function hasImagePreviews(): bool
    {
        $previews = $this->getImagePreviews();
        return !empty($previews);
    }

    public function isPublished(): bool
    {
        return $this->record->getState() === 'published';
    }

}