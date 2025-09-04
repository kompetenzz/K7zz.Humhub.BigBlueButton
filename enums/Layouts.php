<?php
namespace k7zz\humhub\bbb\enums;

class Layouts
{
    public const CUSTOM_LAYOUT = 'CUSTOM_LAYOUT';
    public const SMART_LAYOUT = 'SMART_LAYOUT';
    public const PRESENTATION_FOCUS = 'PRESENTATION_FOCUS';
    public const VIDEO_FOCUS = 'VIDEO_FOCUS';

    public static function values(): array
    {
        return [
            self::CUSTOM_LAYOUT,
            self::SMART_LAYOUT,
            self::PRESENTATION_FOCUS,
            self::VIDEO_FOCUS,
        ];
    }
    public static function options(): array
    {
        return [
            self::CUSTOM_LAYOUT => \Yii::t('BbbModule.base', 'Custom Layout'),
            self::SMART_LAYOUT => \Yii::t('BbbModule.base', 'Smart Layout'),
            self::PRESENTATION_FOCUS => \Yii::t('BbbModule.base', 'Presentation Focus'),
            self::VIDEO_FOCUS => \Yii::t('BbbModule.base', 'Video Focus'),
        ];
    }
    public static function descriptions(): array
    {
        return [
            self::CUSTOM_LAYOUT => \Yii::t('BbbModule.base', 'User defined layout'),
            self::SMART_LAYOUT => \Yii::t('BbbModule.base', 'Automagically adjusts the layout based on the number of participants and shared content'),
            self::PRESENTATION_FOCUS => \Yii::t('BbbModule.base', 'Set focus on shared content'),
            self::VIDEO_FOCUS => \Yii::t('BbbModule.base', 'Set focus on video participants'),
        ];
    }
}
