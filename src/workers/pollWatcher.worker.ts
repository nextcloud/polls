/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import axios, { AxiosError, AxiosInstance } from 'axios'

const MAX_ERRORS = 5
const SLEEP_TIMEOUT_DEFAULT = 30000

let lastUpdated = Math.floor(Date.now() / 1000)
let http: AxiosInstance
let consecutiveErrors = 0

const sleep = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms))

self.onmessage = async (props) => {
	const {
		updateType,
		pollId,
		interval = SLEEP_TIMEOUT_DEFAULT,
		baseUrl,
		token,
		watcherId,
	} = props.data

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

	if (updateType === 'noPolling') {
		self.postMessage({ type: 'info', message: '[Worker] noPolling: exiting.' })
		self.close()
		return
	}

	const run = async () => {
		try {
			let endPoint = `poll/${pollId}/watch`
			if (token) {
				endPoint = `s/${token}/watch`
			}

			const response = await http.get(endPoint, {
				params: { offset: lastUpdated },
			})

			consecutiveErrors = 0

			if (response.status === 200 && response.data.updates?.length > 0) {
				lastUpdated =
					response.data.updates[response.data.updates.length - 1].updated
				self.postMessage({
					type: 'update',
					message: '[Worker] 200 got updates',
					updates: response.data.updates,
				})
			} else if (response.status === 304) {
				self.postMessage({
					type: 'info',
					message: '[Worker] 304 – no changes',
				})
			} else {
				self.postMessage({
					type: 'info',
					message: '[Worker] 200 but no updates',
				})
			}
		} catch (error) {
			const err = error as AxiosError

			if (err.code === 'ECONNABORTED' || err.code === 'ERR_CANCELED') {
				self.postMessage({
					type: 'debug',
					message: '[Worker] Request aborted by intention',
				})
				return
			}

			consecutiveErrors = consecutiveErrors + 1

			self.postMessage({
				type: 'error',
				message: `[Worker] Request failed (${consecutiveErrors}/${MAX_ERRORS})`,
			})

			if (consecutiveErrors >= MAX_ERRORS) {
				self.postMessage({
					type: 'fatal',
					message: `[Worker] Stopping after ${MAX_ERRORS} consecutive errors`,
				})
				self.close()
				return
			}

			await sleep(interval)
		}
	}

	if (updateType === 'periodicPolling') {
		self.postMessage({
			type: 'info',
			message: '[Worker] Started periodic polling.',
		})
		while (true) {
			await run()
			await sleep(interval)
		}
	}

	if (updateType === 'longPolling') {
		self.postMessage({ type: 'info', message: '[Worker] Started long polling.' })
		while (true) {
			await run()
		}
	}
}
