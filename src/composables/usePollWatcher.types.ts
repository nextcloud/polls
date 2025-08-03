/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export type MessageType = 'status' | 'info' | 'error' | 'debug' | 'fatal' | 'update'
export type WatcherMode = 'noPolling' | 'periodicPolling' | 'longPolling'
export type WatcherStatus =
	| 'starting'
	| 'running'
	| 'stopped'
	| 'error'
	| 'stopping'
	| 'idle'
	| 'modeChanged'

export type WatcherData = {
	id: number
	pollId: number
	table: string
	updated: number
	sessionId: string
}
export type WatcherProps = {
	pollId: number | null | undefined
	mode: WatcherMode
	interval?: number
	baseUrl: string
	token: string | null | undefined
	watcherId: string
	lastUpdate?: number
}
export type WorkerResponse = {
	type: MessageType
	status: WatcherStatus
	mode: WatcherMode
	interval: number
	message: string
	updates?: undefined | WatcherData[]
	lastUpdate?: undefined | number
	params?: undefined | WatcherProps
}
