<?php
/**
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
namespace OCA\Polls\Activity;

use OCP\Activity\IFilter;
use OCP\IL10N;
use OCP\IURLGenerator;

class PollChanges implements IFilter {
	/** @var IL10N */
	protected $l10n;

	/** @var IURLGenerator */
	protected $urlGenerator;

	/**
	 * @param IL10N $l10n
	 * @param IURLGenerator $urlGenerator
	 */
	public function __construct(
		IL10N $l10n,
		IURLGenerator $urlGenerator
	) {
		$this->l10n = $l10n;
		$this->urlGenerator = $urlGenerator;
	}

	public function getIdentifier() : string {
		return 'polls';
	}

	public function getName() : string {
		return $this->l10n->t('Poll changes');
	}

	public function getIcon() : string {
		return $this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('polls', 'polls.svg'));
	}

	public function getPriority() : int {
		return 70;
	}

	public function allowedApps() : array {
		return ['polls'];
	}

	public function filterTypes(array $types) : array {
		return ['poll_add', 'vote_set'];
	}
}
