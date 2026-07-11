/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

function randomId(): string {
	return Math.random()
		.toString(36)
		.replace(/[^a-z]+/g, '')
		.slice(2, 12)
}

export { randomId }
