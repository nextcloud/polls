<?php
/**
 * @copyright Copyright (c) 2022 Michael Longo <contact@tiller.fr>
 *
 * @author Michael Longo <contact@tiller.fr>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Polls\Dashboard;

use OCP\Dashboard\IWidget;
use OCP\IL10N;
use OCP\IURLGenerator;

class PollWidget implements IWidget {
	public function __construct(
		private IL10N $l10n,
		private IURLGenerator $urlGenerator
	) {
	}

	public function getId(): string {
		return 'polls';
	}

	public function getTitle(): string {
		return $this->l10n->t('Polls');
	}

	public function getOrder(): int {
		return 50;
	}

	public function getIconClass(): string {
		return 'icon-polls-dark';
	}

	public function getUrl(): ?string {
		return $this->urlGenerator->linkToRouteAbsolute('polls.page.index');
	}

	public function load(): void {
		\OCP\Util::addScript('polls', 'polls-dashboard');
	}
}
