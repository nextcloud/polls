/* jshint esversion: 6 */
/**
 * @copyright Copyright (c) 2022 Rene Gieling <github@dartcafe.de>
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

/**
 * @param {string} cookieName Cookie name
 * @param {string} cookieValue Cookie value
 * @param {number} cookieExpiration expiration from now in seconds
 */
const setCookie = (cookieName, cookieValue, cookieExpiration) => {
	const currentTime = new Date()
	currentTime.setTime(currentTime.getTime() + cookieExpiration)
	const cookieExpiry = `expires=${currentTime.toUTCString()}`
	document.cookie = `${cookieName}=${cookieValue};${cookieExpiry};path=/`
}

/**
 * @param {string} cookieName Cookie name to read
 */
const getCookie = (cookieName) => {
	const name = `${cookieName}=`
	const cookiesArray = decodeURIComponent(document.cookie).split(';')
	for (let i = 0; i < cookiesArray.length; i++) {
		let cookie = cookiesArray[i]
		while (cookie.charAt(0) === ' ') {
			cookie = cookie.substring(1)
		}
		if (cookie.indexOf(name) === 0) {
			return cookie.substring(name.length, cookie.length)
		}
	}
	return ''
}

export { getCookie, setCookie }
