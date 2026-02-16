<?php
namespace k7zz\humhub\bbb\models;

use BigBlueButton\Core\PlaybackFormat;
use BigBlueButton\Core\Record;
use Yii;
class Recording
{
    private $record;
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

    /**
     * @return PlaybackFormat[]
     */
    public function getFormats(): array
    {
        return $this->record->getPlaybackFormats();
    }

    /**
     * Returns the primary (first) format, used for thumbnail previews.
     */
    public function getPrimaryFormat(): ?PlaybackFormat
    {
        return $this->record->getPlaybackFormats()[0] ?? null;
    }

    public function getUrl(): ?string
    {
        return $this->getPrimaryFormat()?->getUrl();
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
        return $this->getPrimaryFormat()?->getImagePreviews() ?? [];
    }

    public function hasImagePreviews(): bool
    {
        return !empty($this->getImagePreviews());
    }

    public function isPublished(): bool
    {
        return $this->record->getState() === 'published';
    }

    /**
     * Returns a human-readable label for a playback format type.
     */
    public static function formatLabel(string $type): string
    {
        return match ($type) {
            'presentation' => Yii::t('BbbModule.base', 'Presentation'),
            'video' => Yii::t('BbbModule.base', 'Video'),
            'podcast' => Yii::t('BbbModule.base', 'Podcast'),
            'screenshare' => Yii::t('BbbModule.base', 'Screenshare'),
            'notes' => Yii::t('BbbModule.base', 'Notes'),
            default => ucfirst($type),
        };
    }

    /**
     * Returns a FontAwesome icon class for a playback format type.
     */
    public static function formatIcon(string $type): string
    {
        return match ($type) {
            'presentation' => 'fa-desktop',
            'video' => 'fa-film',
            'podcast' => 'fa-microphone',
            'screenshare' => 'fa-window-maximize',
            'notes' => 'fa-file-text-o',
            default => 'fa-play-circle',
        };
    }
}
