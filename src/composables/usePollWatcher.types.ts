/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export type WatcherResponse = {
	id: number
	pollId: number
	table: string
	updated: number
	sessionId: string
}
