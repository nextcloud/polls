/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
class Exception extends Error {
	constructor(message: string | undefined) {
		super(message)
		this.name = 'Exception'
	}
}

class NotReady extends Error {
	constructor(message: string | undefined) {
		super(message)
		this.name = 'NotReady'
	}
}

class InvalidJSON extends Error {
	constructor(message: string | undefined) {
		super(message)
		this.name = 'InvalidJSON'
	}
}

export { Exception, InvalidJSON, NotReady }
