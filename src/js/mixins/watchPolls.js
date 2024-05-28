/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { mapState } from 'vuex'
import { InvalidJSON } from '../Exceptions/Exceptions.js'
import { PollsAPI, PublicAPI } from '../Api/index.js'
import { emit } from '@nextcloud/event-bus'
import { Logger } from '../helpers/index.js'

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
		...mapState({
			updateType: (state) => state.acl.appSettings.updateType,
		}),

		watchDisabled() {
			return this.updateType === 'noPolling'
				|| this.retryCounter === null
				|| this.retryCounter >= MAX_TRIES
		},
	},

	methods: {
		async watchPolls() {
			this.retryCounter = 0

			while (!this.watchDisabled) {
				try {
					const response = await this.fetchUpdates()

					if (response.headers['content-type'].includes('application/json')) {
						this.retryCounter = 0
						response.data.updates.forEach((item) => {
							this.lastUpdated = Math.max(item.updated, this.lastUpdated)
						})

						emit('polls:stores:load', response.data.updates)
					} else {
						throw new InvalidJSON(`No JSON response recieved, got "${response.headers['content-type']}"`)
					}

				} catch (error) {
					await this.handleConnectionException(error)
				}

				if (this.watchDisabled) {
					return
				}

				// sleep if request was invalid or polling is set to "peeriodicPolling"
				if (this.watchDisabled || this.retryCounter) {
					await this.sleep()
					Logger.debug(`Continue ${this.updateType} after sleep`)
				}

				// avoid requests when app is in background and pause
				while (document.hidden || !navigator.onLine) {
					if (navigator.onLine) {
						Logger.debug(`App in background, pause ${this.updateType}`)
					} else {
						Logger.debug(`Browser is offline, pause ${this.updateType}`)
					}
					await new Promise((resolve) => setTimeout(resolve, 5000))
					Logger.debug('Resume')
				}

			}

			if (this.retryCounter) {
				Logger.debug(`Cancel watch after ${this.retryCounter} failed requests`)
			}
		},

		async fetchUpdates() {
			Logger.debug('Fetch updates')

			if (this.$route.name === 'publicVote') {
				return await PublicAPI.watchPoll(this.$route.params.token, this.lastUpdated)
			}

			return await PollsAPI.watchPoll(this.$route.params.id, this.lastUpdated)
		},

		sleep() {
			const reason = this.retryCounter ? `Connection error, Attempt: ${this.retryCounter}/${MAX_TRIES})` : this.updateType
			Logger.debug(`Sleep for ${this.sleepTimeout} seconds (reason: ${reason})`)
			return new Promise((resolve) => setTimeout(resolve, this.sleepTimeout * 1000))
		},

		async handleConnectionException(error) {
			if (error.response?.status === 304) {
				// this is a wanted response, no updates where found.
				// resume to normal operation
				Logger.debug(`No updates - continue ${this.updateType}`)
				this.retryCounter = 0
				return
			}

			if (error?.code === 'ERR_NETWORK') {
				Logger.debug(`Possibly offline - continue ${this.updateType}`)
				return
			}

			// Errors, which allow a retry. Increase counter and resume to normal operation
			this.retryCounter += 1

			if (error?.response?.status === 503) {
				// Server possibly in maintenance mode
				this.sleepTimeout = error?.response?.headers['retry-after'] ?? SLEEP_TIMEOUT_DEFAULT
				Logger.debug(`Service not avaiable - retry ${this.updateType} after ${this.sleepTimeout} seconds`)
				return
			}

			// Watch has to be canceled
			if (error?.code === 'ERR_CANCELED' || error?.code === 'ECONNABORTED') {
				Logger.debug('Watch canceled')
			} else {
				Logger.debug(`No response - ${this.updateType} aborted - failed request ${this.retryCounter}/${MAX_TRIES}`, error)
			}

			this.retryCounter = null
		},
	},
}
