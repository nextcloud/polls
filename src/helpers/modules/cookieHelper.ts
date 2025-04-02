/**
 * SPDX-FileCopyrightText: 2022 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * @param cookieName Cookie name
 * @param cookieValue Cookie value
 * @param cookieExpiration expiration from now in seconds
 */
const setCookie = (
	cookieName: string,
	cookieValue: string = '',
	cookieExpiration: number = 360,
) => {
	const expirationTime = new Date()
	expirationTime.setTime(expirationTime.getTime() + cookieExpiration)
	document.cookie = `${cookieName}=${cookieValue};expires=${expirationTime.toUTCString()};path=/`
}

/**
 * @param cookieName Cookie name to read
 * @return Cookie string ('name=value')
 */
function findCookie(cookieName: string): string | undefined {
	return document.cookie
		.split(';')
		.find((cookie) => cookie.split('=')[0] === cookieName)
}

function findCookieByValue(searchValue: string): string | undefined {
	return document.cookie
		.split(';')
		.find((cookie) => cookie.split('=')[1] === searchValue)
}

/**
 * @param cookieName Cookie name to delete
 */
const deleteCookie = (cookieName: string): void => {
	setCookie(cookieName, 'deleted', -100)
}

/**
 * Shortcut to retrieve the cookie value directly or an empty strin, if not found
 * @param cookieName
 */
const getCookieValue = (cookieName: string): string => {
	const cookie = findCookie(cookieName)
	if (cookie) {
		return cookie.split('=')[1]
	}
	return ''
}

/**
 * @param searchValue Cookie value to search for
 */
function deleteCookieByValue(searchValue: string): string | void {
	const foundCookie = findCookieByValue(searchValue)
	if (!foundCookie) {
		return
	}
	const [cookieName, cookieValue] = foundCookie.split('=')

	if (cookieValue === searchValue) {
		deleteCookie(cookieName)
		return cookieName.trim()
	}
}

export {
	getCookieValue,
	findCookie,
	setCookie,
	deleteCookie,
	deleteCookieByValue,
	findCookieByValue,
}
