<?php
	/**
	 * @copyright Copyright (c) 2017 Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
	 *
	 * @author Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
	 * @author René Gieling <github@dartcafe.de>
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



	\OCP\Util::addStyle('polls', 'main');
	\OCP\Util::addStyle('polls', 'flex');
	\OCP\Util::addStyle('polls', 'createpoll');
	\OCP\Util::addStyle('polls', 'sidebar');
	\OCP\Util::addStyle('polls', 'createpoll-newui');
	\OCP\Util::addStyle('polls', 'vendor/jquery.ui.timepicker');

	\OCP\Util::addscript('polls', 'vendor/jquery.ui.timepicker');

?>

<div id="create-poll"></div>
<?php \OCP\Util::addScript('polls', 'create-poll'); ?>
