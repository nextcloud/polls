<?php
/*
 * @copyright Copyright (c) 2021 René Gieling <github@dartcafe.de>
 *
 * @author René Gieling <github@dartcafe.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Polls\Event;

use OCA\Polls\Db\Comment;

abstract class CommentEvent extends BaseEvent {
	public const ADD = 'comment_add';
	public const DELETE = 'comment_delete';

	public function __construct(
		protected Comment $comment,
	) {
		parent::__construct($comment);
		$this->activityObject = 'poll';
		$this->activitySubjectParams['comment'] = [
			'type' => 'highlight',
			'id' => $comment->getId(),
			'name' => $comment->getComment(),
		];
	}

	public function getComment(): Comment {
		return $this->comment;
	}
}
