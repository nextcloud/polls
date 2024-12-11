<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Event;

use OCA\Polls\Db\Share;
use OCA\Polls\Model\UserBase;

abstract class ShareEvent extends BaseEvent {
	public const ADD = 'share_add';
	public const ADD_PUBLIC = 'share_add_public';
	public const CHANGE_EMAIL = 'share_change_email';
	public const CHANGE_DISPLAY_NAME = 'share_change_display_name';
	public const CHANGE_LABEL = 'share_change_label';
	public const CHANGE_TYPE = 'share_change_type';
	public const CHANGE_REG_CONSTR = 'share_change_reg_const';
	public const REGISTRATION = 'share_registration';
	public const DELETE = 'share_delete';
	public const RESTORE = 'share_restore';
	public const LOCKED = 'share_locked';
	public const UNLOCKED = 'share_unlocked';

	public function __construct(
		protected Share $share,
	) {
		parent::__construct($share);
		$this->activityObjectType = 'poll';
		$this->log = false;
		$this->share = $share;
		$this->activitySubjectParams['shareType'] = $this->share->getRichObjectString();
		$this->activitySubjectParams['sharee'] = $this->getSharee()->getRichObjectString();
	}

	protected function getSharee(): UserBase {
		return $this->userMapper->getUserFromShare($this->share);
	}
}
