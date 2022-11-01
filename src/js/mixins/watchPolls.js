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

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'
import { mapState } from 'vuex'
import { InvalidJSON } from '../Exceptions/Exceptions.js'
import axiosDefaultConfig from '../helpers/AxiosDefault.js'

const defaultSleepTimeout = 30

export const watchPolls = {
	data() {
		return {
			cancelToken: null,
			restart: false,
			watching: true,
			lastUpdated: Math.round(Date.now() / 1000),
			retryCounter: 0,
			maxTries: 5,
			endPoint: '',
			isLoggedin: !!getCurrentUser(),
			isAdmin: !!getCurrentUser()?.isAdmin,
			sleepTimeout: defaultSleepTimeout, // seconds
			gotValidResponse: true,
		}
	},

	computed: {
		...mapState({
			updateType: (state) => state.appSettings.updateType,
		}),
	},

	methods: {
		async watchPolls() {
			if (this.cancelToken) {
				this.cancelWatch() // there is already a cancelToken, cancel the previous session
			}

			this.cancelToken = axios.CancelToken.source() // get a new cancel token

			while (this.retryCounter < this.maxTries) {
				this.sleepTimeout = defaultSleepTimeout // reset sleep timer to default
				this.gotValidResponse = false

				if (this.updateType === 'noPolling') {
					// leave if polling is disabled
					console.debug('[polls]', 'Polling for updates is disabled. Cancel watch.')
					this.cancelWatch()
					return
				}

				// loop while tab is hidden and avoid further requests
				while (document.hidden) {
					console.debug('[polls]', 'app is in background')
					await new Promise((resolve) => setTimeout(resolve, 2000))
				}

				try {
					console.debug('[polls]', 'Watch for updates')
					await this.handleResponse(await this.fetchUpdates())

				} catch (e) {
					if (axios.isCancel(e)) {
						this.handleCanceledRequest()
					} else {
						this.handleConnectionError(e)
					}
				}

				// sleep if request was invalid or polling is set to something else than "longPolling"
				if (this.updateType !== 'longPolling' || !this.gotValidResponse) {
					await this.sleep()
					console.debug('[polls]', 'continue after sleep')
				}
			}

			// invalidate the cancel token before leaving
			this.cancelToken = null

			if (this.retryCounter) {
				console.debug('[polls]', `Cancel watch after ${this.retryCounter} failed requests`)
			}
		},

		async fetchUpdates() {
			if (this.$route.name === 'publicVote') {
				this.endPoint = `apps/polls/s/${this.$route.params.token}/watch`
			} else {
				this.endPoint = `apps/polls/poll/${this.$route.params.id ?? 0}/watch`
			}

			await this.$store.dispatch('appSettings/get')

			const response = await axios.get(generateUrl(this.endPoint), {
				...axiosDefaultConfig,
				params: { offset: this.lastUpdated },
				cancelToken: this.cancelToken.token,
			})

			return response

		},

		cancelWatch() {
			this.cancelToken.cancel()
		},

		sleep() {
			let reason = `Connection error, Attempt: ${this.retryCounter}/${this.maxTries})`

			if (this.gotValidResponse) {
				reason = this.updateType
			}

			console.debug('[polls]', `Sleep for ${this.sleepTimeout} seconds (reason: ${reason})`)
			return new Promise((resolve) => setTimeout(resolve, this.sleepTimeout * 1000))
		},

		handleResponse(response) {
			if (response.headers['content-type'].includes('application/json')) {
				this.gotValidResponse = true
				console.debug('[polls]', `Update detected (${this.updateType})`, response.data.updates)
				this.loadTables(response.data.updates)
				this.retryCounter = 0 // reset retryCounter after we got a valid response
				return
			}

			this.gotValidResponse = false
			throw new InvalidJSON(`No JSON response recieved, got "${response.headers['content-type']}"`)
		},

		handleCanceledRequest() {
			console.debug('[polls]', 'Fetch canceled')
			this.cancelToken = axios.CancelToken.source()
		},

		async handleConnectionError(e) {
			if (e.response?.status === 304) {
				console.debug('[polls]', `No updates (using ${this.updateType})`)
				this.gotValidResponse = true
				this.retryCounter = 0 // reset retryCounter, after we get a 304
				return
			}

			this.retryCounter += 1

			if (e?.response?.status === 503) {
				// Server possibly in maintenance mode
				this.sleepTimeout = e.response.headers['retry-after'] ?? this.sleepTimeout
				console.debug('[polls]', `Service not avaiable - retry after ${this.sleepTimeout} seconds`)
				return
			}
			if (e.response) {
				console.error('[polls]', e)
				return
			}

			console.debug('[polls]', e.message ?? `No response - request aborted - failed request ${this.retryCounter}/${this.maxTries}`)
		},

		async loadTables(tables) {
			let dispatches = ['activity/list']
			console.debug('[polls]', 'fetching updates', tables)
			tables.forEach((item) => {
				this.lastUpdated = Math.max(item.updated, this.lastUpdated)
				if (item.table === 'polls') {
					if (this.isAdmin) {
						console.debug('[polls]', 'update admin view', item.table)
						// If user is an admin, also load admin list
						dispatches = [...dispatches, 'pollsAdmin/list']
					}

					if (item.pollId === parseInt(this.$route.params.id ?? this.$store.state.share.pollId)) {
						// if current poll is affected, load current poll configuration
						console.debug('[polls]', 'current poll', item.table)
						dispatches = [...dispatches, 'poll/get']
					}

					if (this.isLoggedin) {
						// if user is an authorized user load polls list
						console.debug('[polls]', 'update list', item.table)
						dispatches = [...dispatches, `${item.table}/list`]
					}
				} else if (!this.isLoggedin && (item.table === 'shares')) {
					// if current user is guest and table is shares only reload current share
					dispatches = [...dispatches, 'share/get']
				} else {
					// otherwise load table
					dispatches = [...dispatches, `${item.table}/list`]
				}
			})
			dispatches = [...new Set(dispatches)] // remove duplicates
			await Promise.all(dispatches.map((dispatches) => this.$store.dispatch(dispatches)))
			await this.$store.dispatch('combo/cleanUp')
		},

	},
}
