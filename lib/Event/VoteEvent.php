<?php
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Event;

use OCA\Polls\Db\Vote;

/**
 * @psalm-suppress UnusedProperty
 */
abstract class VoteEvent extends BaseEvent {
	public const SET = 'vote_set';

	public function __construct(
		protected Vote $vote,
	) {
		parent::__construct($vote);
		$this->activityObjectType = 'poll';
		$this->vote = $vote;
	}
}
