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

import { getCurrentUser } from '@nextcloud/auth'
import { mapState } from 'vuex'
import { InvalidJSON } from '../Exceptions/Exceptions.js'
import { PollsAPI } from '../Api/polls.js'
import { PublicAPI } from '../Api/public.js'

const SLEEP_TIMEOUT_DEFAULT = 30
const MAX_TRIES = 5

export const watchPolls = {
	data() {
		return {
			restart: false,
			watching: true,
			lastUpdated: Math.round(Date.now() / 1000),
			endPoint: '',
			isLoggedin: !!getCurrentUser(),
			isAdmin: !!getCurrentUser()?.isAdmin,
			sleepTimeout: SLEEP_TIMEOUT_DEFAULT, // seconds
		}
	},

	computed: {
		...mapState({
			updateType: (state) => state.appSettings.updateType,
		}),

		pollingDisabled() {
			if (this.updateType !== 'noPolling') {
				return false
			}

			console.debug('[polls]', 'Polling for updates is disabled. Cancel watch.')
			return true
		},
	},

	methods: {
		async watchPolls() {
			const sleepTimeout = SLEEP_TIMEOUT_DEFAULT

			let retryCounter = 0

			console.debug('[polls]', this.pollingDisabled ? 'Watch is disabled' : `Start ${this.updateType} for updates`)

			while (retryCounter < MAX_TRIES && !this.pollingDisabled) {

				// avoid requests when app is in background and pause
				while (document.hidden) {
					console.debug('[polls]', `App in background, pause ${this.updateType}`)
					await new Promise((resolve) => setTimeout(resolve, 5000))
				}

				try {
					const response = await this.fetchUpdates()

					if (response.headers['content-type'].includes('application/json')) {
						retryCounter = 0
						this.loadStores(response.data.updates)
					} else {
						throw new InvalidJSON(`No JSON response recieved, got "${response.headers['content-type']}"`)
					}

				} catch (e) {
					this.sleepTimeout = e?.response?.headers['retry-after'] ?? SLEEP_TIMEOUT_DEFAULT
					retryCounter = await this.handleConnectionException(e, retryCounter, sleepTimeout)
				}

				// sleep if request was invalid or polling is set to "peeriodicPolling"
				if (this.updateType === 'periodicPolling' || retryCounter) {
					await this.sleep(sleepTimeout)
					console.debug('[polls]', `Continue ${this.updateType} after sleep`)
				} else if (this.updateType === 'noPolling') {
					console.debug('[polls]', 'Watch got disabled')
				}
			}

			if (retryCounter) {
				console.debug('[polls]', `Cancel watch after ${retryCounter} failed requests`)
			}
		},

		async fetchUpdates() {
			await this.$store.dispatch('appSettings/get')

			if (this.$route.name === 'publicVote') {
				return await PublicAPI.watchPoll(this.$route.params.token, this.lastUpdated)
			}

			return await PollsAPI.watchPoll(this.$route.params.id, this.lastUpdated)
		},

		sleep(retryCounter, sleepTimeout) {
			const reason = retryCounter ? `Connection error, Attempt: ${retryCounter}/${MAX_TRIES})` : this.updateType
			console.debug('[polls]', `Sleep for ${sleepTimeout} seconds (reason: ${reason})`)
			return new Promise((resolve) => setTimeout(resolve, sleepTimeout * 1000))
		},

		async handleConnectionException(e, retryCounter, sleepTimeout) {
			retryCounter += 1

			if (e?.code === 'ERR_CANCELED') {
				return 0
			}

			if (e.response?.status === 304) {
				console.debug('[polls]', `No updates - continue ${this.updateType}`)
				return 0
			}

			if (e?.response?.status === 503) {
				// Server possibly in maintenance mode
				console.debug('[polls]', `Service not avaiable - retry ${this.updateType} after ${sleepTimeout} seconds`)
				return retryCounter
			}

			if (e.response) {
				console.error('[polls]', e)
				return retryCounter
			}

			console.debug('[polls]', e.message ?? `No response - ${this.updateType} aborted - failed request ${retryCounter}/${MAX_TRIES}`)
		},

		async loadStores(stores) {
			console.debug('[polls]', 'Updates detected', stores)

			let dispatches = ['activity/list']

			stores.forEach((item) => {
				this.lastUpdated = Math.max(item.updated, this.lastUpdated)

				if (item.table === 'polls') {

					// If user is an admin, also load admin list
					if (this.isAdmin) dispatches = [...dispatches, 'pollsAdmin/list']

					// if user is an authorized user load polls list and combo
					if (this.isLoggedin) dispatches = [...dispatches, `${item.table}/list`, 'combo/cleanUp']

					// if current poll is affected, load current poll configuration
					if (item.pollId === this.$store.state.poll.id) {
						dispatches = [...dispatches, 'poll/get']
					}

				} else if (!this.isLoggedin && (item.table === 'shares')) {
					// if current user is guest and table is shares only reload current share
					dispatches = [...dispatches, 'share/get']
				} else {
					// otherwise just load particulair store
					dispatches = [...dispatches, `${item.table}/list`]
				}
			})
			dispatches = [...new Set(dispatches)] // remove duplicates and add combo
			return Promise.all(dispatches.map((dispatches) => this.$store.dispatch(dispatches)))
		},

	},
}
