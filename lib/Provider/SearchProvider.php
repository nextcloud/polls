<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2020 René Gieling <github@dartcafe.de>
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

declare(strict_types=1);


namespace OCA\Polls\Provider;

use OCA\Polls\AppConstants;
use OCA\Polls\Db\Poll;
use OCA\Polls\Model\Search\PollsSearchResultEntry;
use OCA\Polls\Service\PollService;
use OCP\IL10N;
use OCP\IUser;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;

class SearchProvider implements IProvider {
	public function __construct(
		private IL10N $l10n,
		// private IURLGenerator $urlGenerator,
		private PollService $pollService,
	) {
	}

	public function getId(): string {
		return 'poll';
	}

	public function getName(): string {
		return $this->l10n->t('Polls');
	}

	public function search(IUser $user, ISearchQuery $query): SearchResult {
		$cursor = $query->getCursor();
		$polls = $this->pollService->search($query);

		$results = array_map(function (Poll $poll) {
			return [
				'object' => $poll,
				'entry' => new PollsSearchResultEntry($poll)
			];
		}, $polls);

		$resultEntries = array_map(function (array $result) {
			return $result['entry'];
		}, $results);

		return SearchResult::complete(
			$this->l10n->t('Polls'),
			$resultEntries
		);
	}

	public function getOrder(string $route, array $routeParameters): int {
		if (in_array(strtolower($route), [AppConstants::APP_ID . '.page.index', AppConstants::APP_ID . '.page.vote'])) {
			return -5;
		}
		return 51;
	}
}
