/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { InvalidJSON } from '../Exceptions/Exceptions.js'
import { PollsAPI, PublicAPI } from '../Api/index.js'
import { Logger } from '../helpers/index.ts'
import { mapStores } from 'pinia'
import { useSessionStore } from '../stores/session.ts'
import { UpdateType } from '../Types/index.ts'

const SLEEP_TIMEOUT_DEFAULT = 30
const MAX_TRIES = 5

export const watchPolls = {
	data() {
		return {
			restart: false,
			watching: true,
			lastUpdated: Math.round(Date.now() / 1000),
			endPoint: '',
			sleepTimeout: SLEEP_TIMEOUT_DEFAULT, // seconds
			retryCounter: 0,
		}
	},

	computed: {
		...mapStores(useSessionStore),

		watchDisabled() {
			return (
				this.sessionStore.appSettings.updateType === UpdateType.NoPolling ||
				this.retryCounter === null ||
				this.retryCounter >= MAX_TRIES
			)
		},
	},

	methods: {
		async watchPolls() {
			this.retryCounter = 0

			while (!this.watchDisabled) {
				try {
					const response = await this.fetchUpdates()

					if (
						response.headers['content-type'].includes('application/json')
					) {
						this.retryCounter = 0
						response.data.updates.forEach((item) => {
							this.lastUpdated = Math.max(
								item.updated,
								this.lastUpdated,
							)
						})
					} else {
						throw new InvalidJSON(
							`No JSON response recieved, got "${response.headers['content-type']}"`,
						)
					}
				} catch (error) {
					await this.handleConnectionException(error)
				}

				if (this.watchDisabled) {
					return
				}

				// sleep if request was invalid or polling is set to "periodicPolling"
				if (this.watchDisabled || this.retryCounter) {
					await this.sleep()
					Logger.debug(
						`Continue ${this.sessionStore.appSettings.updateType} after sleep`,
					)
				}

				// avoid requests when app is in background and pause
				while (document.hidden || !navigator.onLine) {
					if (navigator.onLine) {
						Logger.debug(
							`App in background, pause ${this.sessionStore.appSettings.updateType}`,
						)
					} else {
						Logger.debug(
							`Browser is offline, pause ${this.sessionStore.appSettings.updateType}`,
						)
					}
					await new Promise((resolve) => setTimeout(resolve, 5000))
					Logger.debug('Resume')
				}
			}

			if (this.retryCounter) {
				Logger.debug(
					`Cancel watch after ${this.retryCounter} failed requests`,
				)
			}
		},

		async fetchUpdates() {
			Logger.debug('Fetch updates')

			if (this.$route.name === 'publicVote') {
				return await PublicAPI.watchPoll(
					this.$route.params.token,
					this.lastUpdated,
				)
			}

			return await PollsAPI.watchPoll(this.$route.params.id, this.lastUpdated)
		},

		sleep() {
			const reason = this.retryCounter
				? `Connection error, Attempt: ${this.retryCounter}/${MAX_TRIES})`
				: this.sessionStore.appSettings.updateType
			Logger.debug(
				`Sleep for ${this.sleepTimeout} seconds (reason: ${reason})`,
			)
			return new Promise((resolve) =>
				setTimeout(resolve, this.sleepTimeout * 1000),
			)
		},

		async handleConnectionException(error) {
			if (error.response?.status === 304) {
				// this is a wanted response, no updates where found.
				// resume to normal operation
				Logger.debug(
					`No updates - continue ${this.sessionStore.appSettings.updateType}`,
				)
				this.retryCounter = 0
				return
			}

			if (error?.code === 'ERR_NETWORK') {
				Logger.debug(
					`Possibly offline - continue ${this.sessionStore.appSettings.updateType}`,
				)
				return
			}

			// Errors, which allow a retry. Increase counter and resume to normal operation
			this.retryCounter += 1

			if (error?.response?.status === 503) {
				// Server possibly in maintenance mode
				this.sleepTimeout =
					error?.response?.headers['retry-after'] ?? SLEEP_TIMEOUT_DEFAULT
				Logger.debug(
					`Service not avaiable - retry ${this.sessionStore.appSettings.updateType} after ${this.sleepTimeout} seconds`,
				)
				return
			}

			// Watch has to be canceled
			if (error?.code === 'ERR_CANCELED' || error?.code === 'ECONNABORTED') {
				Logger.debug('Watch canceled')
			} else {
				Logger.debug(
					`No response - ${this.sessionStore.appSettings.updateType} aborted - failed request ${this.retryCounter}/${MAX_TRIES}`,
					error,
				)
			}

			this.retryCounter = null
		},
	},
}
