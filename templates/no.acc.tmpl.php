<?php
	/**
	 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
	 *
	 * @author Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
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

	use OCP\Util;
	Util::addStyle('polls', 'main');
?>
<div id="emptycontent" class="">
	<div class="icon-polls"></div>
	<h1>
		<?php /** @scrutinizer ignore-call */ p($l->t('Access denied')); ?>
	</h1>
	<h2>
		<?php /** @scrutinizer ignore-call */ p($l->t('You are not allowed to view this poll or the poll does not exist.')); ?>
	</h2>
</div>
