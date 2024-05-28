/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
