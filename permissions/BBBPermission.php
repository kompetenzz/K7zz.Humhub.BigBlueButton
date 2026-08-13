<?php

namespace k7zz\humhub\bbb\permissions;

use humhub\libs\BasePermission;
use humhub\modules\user\models\Group;

/**
 * Base class for all BBB module permissions.
 *
 * Deliberately extends BasePermission (not BaseAdminPermission): the global admin
 * group is allowed by default but not fixed, so each permission can be revoked for
 * admins via Administration → Groups, treating them like regular users.
 */
abstract class BBBPermission extends BasePermission
{
    /** @var string The module ID for this permission. */
    protected $moduleId = 'bbb';


    /** @var string The default state for our permissions. */
    protected $defaultState = self::STATE_DENY;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->defaultAllowedGroups[] = Group::getAdminGroupId();
    }
}
