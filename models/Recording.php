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
        $url = $this->getPrimaryFormat()?->getUrl();
        return $url ? $this->rewriteUrl($url) : null;
    }

    public function getFormatUrl(\BigBlueButton\Core\PlaybackFormat $format): string
    {
        return $this->rewriteUrl($format->getUrl());
    }

    /**
     * Rewrites a BBB recording URL so that scheme, host and port match
     * the configured BBB server URL (the server may return 127.0.0.1 internally).
     */
    private function rewriteUrl(string $url): string
    {
        $url = trim($url); // BBB XML may include surrounding whitespace
        $bbbUrl = rtrim(Yii::$app->getModule('bbb')->settings->get('bbbUrl') ?? '', '/');
        if (!$bbbUrl || !$url) {
            return $url;
        }
        $bbbParts = parse_url($bbbUrl);
        $parts = parse_url($url);
        if (!$bbbParts || !$parts) {
            return $url;
        }
        $scheme = $bbbParts['scheme'] ?? $parts['scheme'] ?? 'http';
        $host = $bbbParts['host'] ?? $parts['host'] ?? '';
        $port = isset($bbbParts['port']) ? ':' . $bbbParts['port'] : '';
        $path = $parts['path'] ?? '';
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';
        $frag = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

        return $scheme . '://' . $host . $port . $path . $query . $frag;
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

    public function isFormatPublished(PlaybackFormat $format): bool
    {
        return RecordingFormat::isPublished($this->record->getRecordId(), $format->getType());
    }

    /**
     * Returns only formats that are published (for non-admin display).
     * @return PlaybackFormat[]
     */
    public function getPublishedFormats(): array
    {
        return array_values(array_filter(
            $this->record->getPlaybackFormats(),
            fn(PlaybackFormat $f) => $this->isFormatPublished($f)
        ));
    }

    public function hasAnyPublishedFormat(): bool
    {
        return !empty($this->getPublishedFormats());
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
