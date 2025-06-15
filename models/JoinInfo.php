<?php
namespace k7zz\humhub\bbb\models;

/**
 * @property int    $id
 * @property string $uuid
 * @property string $moderator_pw
 * …
 */
class JoinInfo
{
    public string $url;
    public string $title;
    public ?string $containerId = null;
}
