<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Model\Search;

use OCA\Polls\Db\Poll;
use OCP\Search\SearchResultEntry;

class PollsSearchResultEntry extends SearchResultEntry {
	public function __construct(Poll $poll) {
		parent::__construct('', $poll->getTitle(), $poll->getDescription(), $poll->getVoteUrl(), 'icon-polls-dark');
	}
}
