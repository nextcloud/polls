<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Event;

use OCA\Polls\Db\Comment;

/**
 * @psalm-suppress UnusedProperty
 */
abstract class CommentEvent extends BaseEvent {
	public const ADD = 'comment_add';
	public const DELETE = 'comment_delete';
	public const RESTORE = 'comment_restore';

	public function __construct(
		protected Comment $comment,
	) {
		parent::__construct($comment);
		$this->activityObjectType = 'poll';
		$this->activitySubjectParams['comment'] = [
			'type' => 'highlight',
			'id' => (string) $comment->getId(),
			'name' => $comment->getComment(),
		];
	}
}
