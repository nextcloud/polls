/* jshint esversion: 6 */
/**
 * @copyright Copyright (c) 2021 René Gieling <github@dartcafe.de>
 *
 * @author René Gieling <github@dartcafe.de>
 *
 * @license  AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

import { mapState } from 'vuex'
import { InvalidJSON } from '../Exceptions/Exceptions.js'
import { PollsAPI, PublicAPI } from '../Api/index.js'
import { emit } from '@nextcloud/event-bus'

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

				} catch (e) {
					await this.handleConnectionException(e)
				}

				if (this.watchDisabled) {
					return
				}

				// sleep if request was invalid or polling is set to "peeriodicPolling"
				if (this.watchDisabled || this.retryCounter) {
					await this.sleep()
					console.debug('[polls]', `Continue ${this.updateType} after sleep`)
				}

				// avoid requests when app is in background and pause
				while (document.hidden || !navigator.onLine) {
					if (navigator.onLine) {
						console.debug('[polls]', `App in background, pause ${this.updateType}`)
					} else {
						console.debug('[polls]', `Browser is offline, pause ${this.updateType}`)
					}
					await new Promise((resolve) => setTimeout(resolve, 5000))
					console.debug('[polls]', 'Resume')
				}

			}

			if (this.retryCounter) {
				console.debug('[polls]', `Cancel watch after ${this.retryCounter} failed requests`)
			}
		},

		async fetchUpdates() {
			console.debug('[polls]', 'Fetch updates')

			if (this.$route.name === 'publicVote') {
				return await PublicAPI.watchPoll(this.$route.params.token, this.lastUpdated)
			}

			return await PollsAPI.watchPoll(this.$route.params.id, this.lastUpdated)
		},

		sleep() {
			const reason = this.retryCounter ? `Connection error, Attempt: ${this.retryCounter}/${MAX_TRIES})` : this.updateType
			console.debug('[polls]', `Sleep for ${this.sleepTimeout} seconds (reason: ${reason})`)
			return new Promise((resolve) => setTimeout(resolve, this.sleepTimeout * 1000))
		},

		async handleConnectionException(e) {
			if (e.response?.status === 304) {
				// this is a wanted response, no updates where found.
				// resume to normal operation
				console.debug('[polls]', `No updates - continue ${this.updateType}`)
				this.retryCounter = 0
				return
			}

			if (e?.code === 'ERR_NETWORK') {
				console.debug('[polls]', `Possibly offline - continue ${this.updateType}`)
				return
			}

			// Errors, which allow a retry. Increase counter and resume to normal operation
			this.retryCounter += 1

			if (e?.response?.status === 503) {
				// Server possibly in maintenance mode
				this.sleepTimeout = e?.response?.headers['retry-after'] ?? SLEEP_TIMEOUT_DEFAULT
				console.debug('[polls]', `Service not avaiable - retry ${this.updateType} after ${this.sleepTimeout} seconds`)
				return
			}

			// Watch has to be canceled
			if (e?.code === 'ERR_CANCELED' || e?.code === 'ECONNABORTED') {
				console.debug('[polls]', 'Watch canceled')
			} else {
				console.debug('[polls]', e.message ?? `No response - ${this.updateType} aborted - failed request ${this.retryCounter}/${MAX_TRIES}`, e)
			}

			this.retryCounter = null
		},
	},
}
