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
const setCookie = (cookieName, cookieValue = '', cookieExpiration = 360) => {
	const expirationTime = (new Date())
	expirationTime.setTime(expirationTime.getTime() + cookieExpiration)
	document.cookie = `${cookieName}=${cookieValue};expires=${expirationTime.toUTCString()};path=/`
}

/**
 * @param {string} cookieName Cookie name to read
 * @return {string} Cookie string ('name=value')
 */
const findCookie = (cookieName) => document.cookie.split(';').find((cookie) => cookie.split('=')[0] === cookieName)

/**
 * @param {string} searchValue Cookie value to search for
 * @return {string} Cookie string ('name=value')
 */
const findCookieByValue = (searchValue) => document.cookie.split(';').find((cookie) => cookie.split('=')[1] === searchValue)

/**
 * @param {string} cookieName Cookie name to delete
 */
const deleteCookie = (cookieName) => {
	setCookie(cookieName, 'deleted', -100)
}

/**
 * Shortcut to retrieve the cookie value directly or an empty strin, if not found
 *
 * @param {string} cookieName Cookie name to read
 * @return {string} Value of the found cookie
 */
const getCookieValue = (cookieName) => {
	const cookie = findCookie(cookieName)
	if (cookie) {
		return cookie.split('=')[1]
	}
	return ''
}

/**
 * @param {string} searchValue Cookie value to search for
 */
const deleteCookieByValue = (searchValue) => {
	const [cookieName, cookieValue] = findCookieByValue(searchValue).split('=')

	if (cookieValue === searchValue) {
		deleteCookie(cookieName)
		return cookieName
	}
}

export { getCookieValue, findCookie, setCookie, deleteCookie, deleteCookieByValue, findCookieByValue }
