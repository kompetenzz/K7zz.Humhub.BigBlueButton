<?php
namespace k7zz\humhub\bbb\models;

/**
 * Data transfer object for join information of a BBB session.
 *
 * Contains the join URL, title, and optional container ID for embedding or redirection.
 *
 * @property string $url       The join URL for the session
 * @property string $title     The title for the join view
 * @property string|null $containerId Optional container ID
 */
class JoinInfo
{
    public string $url;
    public string $title;
    public ?string $containerId = null;
}
