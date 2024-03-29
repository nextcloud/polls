<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2023 René Gieling <github@dartcafe.de>
 *
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

namespace OCA\Polls;

abstract class AppConstants {
	/** @var string */
	public const APP_ID = 'polls';
	/** @var string */
	public const SESSION_KEY_USER_ID = 'ncPollsUserId';
	/** @var string */
	public const SESSION_KEY_SHARE_TOKEN = 'ncPollsPublicToken';
	/** @var string */
	public const SESSION_KEY_SHARE_TYPE = 'ncPollsShareType';
	/** @var string */
	public const CLIENT_ID = 'ncPollsClientId';
	/** @var string */
	public const CLIENT_TZ = 'ncPollsClientTimeZone';
}
