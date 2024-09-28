<?php
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Event;

use OCA\Polls\Db\Comment;

class CommentAddEvent extends CommentEvent {
	public function __construct(
		protected Comment $comment,
	) {
		parent::__construct($comment);
		$this->eventId = self::ADD;
	}
}
