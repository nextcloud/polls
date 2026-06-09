/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export class NotAllowed extends Error {
	constructor(message: string | undefined) {
		super(message)
		this.name = 'NotAllowed'
	}
}
