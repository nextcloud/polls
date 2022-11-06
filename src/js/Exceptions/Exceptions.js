/* jshint esversion: 6 */
/**
 * @copyright Copyright (c) 2021 Rene Gieling <github@dartcafe.de>
 *
 * @author Rene Gieling <github@dartcafe.de>
 *
 * @license  AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
class Exception extends Error {

	constructor(message) {
		super(message)
		this.name = 'Exception'
	}

}

class NotReady extends Error {

	constructor(message) {
		super(message)
		this.name = 'NotReady'
	}

}

class InvalidJSON extends Error {

	constructor(message) {
		super(message)
		this.name = 'InvalidJSON'
	}

}

class CancelledRequest extends Error {

	constructor(message) {
		super(message)
		this.name = 'cancelledRequest'
	}

}

export { Exception, InvalidJSON, NotReady, CancelledRequest }
