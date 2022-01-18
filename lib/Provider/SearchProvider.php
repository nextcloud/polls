<?php
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

use OCA\Polls\Model\Search\PollsSearchResultEntry;
use OCA\Polls\Service\PollService;
use OCA\Polls\Db\Poll;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;

class SearchProvider implements IProvider {

	/** @var IL10N */
	private $l10n;

	/**
	 * @var IURLGenerator
	 */
	private $urlGenerator;

	/**
	 * @var PollService
	 */
	private $pollService;

	public function __construct(
		IL10N $l10n,
		IURLGenerator $urlGenerator,
		PollService $pollService
	) {
		$this->l10n = $l10n;
		$this->urlGenerator = $urlGenerator;
		$this->pollService = $pollService;
	}

	public function getId(): string {
		return 'poll';
	}

	public function getName(): string {
		return $this->l10n->t('polls', 'Polls');
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
			$this->l10n->t('polls', 'Polls'),
			$resultEntries
		);
	}

	public function getOrder(string $route, array $routeParameters): int {
		if (in_array(strtolower($route), ['polls.page.index', 'polls.page.vote'])) {
			return -5;
		}
		return 51;
	}
}
