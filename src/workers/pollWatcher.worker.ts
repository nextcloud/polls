/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import axios, { AxiosError, AxiosInstance, AxiosResponse } from 'axios'
import {
	WatcherMode,
	WatcherData,
	WorkerResponse,
	WatcherProps,
} from '../composables/usePollWatcher.types'

const MAX_ERRORS = 5
const SLEEP_TIMEOUT_DEFAULT = 30000

let lastUpdated = 0
let http: AxiosInstance
let consecutiveErrors = 0

const sleep = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms))
const sendMessage = (message: WorkerResponse) => {
	self.postMessage(message)
}
self.onmessage = async (props: MessageEvent<WatcherProps>) => {
	const {
		mode,
		pollId,
		interval = SLEEP_TIMEOUT_DEFAULT,
		baseUrl,
		token,
		watcherId,
		lastUpdate = lastUpdated,
	} = props.data

	lastUpdated = lastUpdate

	sendMessage({
		type: 'status',
		status: 'starting',
		mode,
		interval,
		message: '[Worker] Recieved new parameters.',
		params: props.data,
	})

	if (!http) {
		http = axios.create({
			baseURL: baseUrl,
			withCredentials: true,
			headers: {
				Accept: 'application/json',
				'Nc-Polls-Client-Id': watcherId,
			},
			validateStatus: (status) => [200, 304].includes(status),
		})
	}

	if (mode === 'noPolling') {
		sendMessage({
			type: 'info',
			status: 'stopped',
			mode,
			interval,
			message: '[Worker] noPolling: exiting.',
		})
		self.close()
		return
	}

	const run = async () => {
		try {
			let endPoint = `poll/${pollId}/watch`
			if (token) {
				endPoint = `s/${token}/watch`
			}

			const response: AxiosResponse<{
				mode: WatcherMode
				updates: WatcherData[]
			}> = await http.get(endPoint, {
				params: { offset: lastUpdated, mode },
			})

			consecutiveErrors = 0

			if (response.status === 200 && response.data.updates?.length > 0) {
				lastUpdated =
					response.data.updates[response.data.updates.length - 1].updated

				sendMessage({
					type: 'update',
					status: 'running',
					mode: response.data.mode || mode,
					interval,
					message: '[Worker] 200 got updates',
					updates: response.data.updates,
					lastUpdate: lastUpdated,
				})
			} else if (response.status === 304) {
				sendMessage({
					type: 'info',
					status: 'running',
					mode,
					interval,
					message: '[Worker] 304 – no changes',
					lastUpdate: lastUpdated,
				})
			} else {
				sendMessage({
					type: 'info',
					status: 'running',
					mode,
					interval,
					message: '[Worker] 200 but no updates',
					lastUpdate: lastUpdated,
				})
			}
		} catch (error) {
			const err = error as AxiosError

			if (err.code === 'ECONNABORTED' || err.code === 'ERR_CANCELED') {
				sendMessage({
					type: 'status',
					status: 'stopping',
					mode,
					interval,
					message: '[Worker] Request aborted by intention',
					lastUpdate: lastUpdated,
				})
				return
			}

			consecutiveErrors = consecutiveErrors + 1

			if (err.status === 409) {
				sendMessage({
					type: 'status',
					status: 'modeChanged',
					mode,
					interval,
					message: '[Worker] 409 server changed mode - reload session',
					lastUpdate: lastUpdated,
				})
			} else {
				sendMessage({
					type: 'error',
					status: 'error',
					mode,
					interval,
					message: `[Worker] Request failed (${consecutiveErrors}/${MAX_ERRORS})`,
				})
			}

			if (consecutiveErrors >= MAX_ERRORS) {
				sendMessage({
					type: 'fatal',
					status: 'error',
					mode,
					interval,
					message: `[Worker] Stopping after ${MAX_ERRORS} consecutive errors`,
				})
				self.close()
				return
			}

			await sleep(interval)
		}
	}

	if (mode === 'periodicPolling') {
		sendMessage({
			type: 'info',
			status: 'starting',
			mode,
			interval,
			message: '[Worker] Started periodic polling.',
		})
		while (true) {
			await run()
			sendMessage({
				type: 'status',
				status: 'idle',
				mode,
				interval,
				message: `[Worker] Sleeping for .${Math.floor(interval / 1000)} s`,
			})
			await sleep(interval)
		}
	}

	if (mode === 'longPolling') {
		sendMessage({
			type: 'info',
			status: 'starting',
			mode,
			interval,
			message: '[Worker] Started long polling.',
		})
		while (true) {
			await run()
		}
	}
}
