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
import { NotReady } from '../Exceptions/Exceptions'
import { getCurrentUser } from '@nextcloud/auth'

export const watchPolls = {
	data() {
		return {
			cancelToken: null,
			restart: false,
			watching: true,
			lastUpdated: Math.round(Date.now() / 1000),
			retryCounter: 0,
			retryTimeout: 30000,
			maxTries: 5,
			endPoint: '',
			isLoggedin: !!getCurrentUser(),
			isAdmin: !!getCurrentUser()?.isAdmin,
			sleepTimeout: 30,
			sessionCount: 0,
			updateType: 'noPolling',
		}
	},

	methods: {
		cancelWatch() {
			console.debug('[polls]', 'Cancel watch')
			this.cancelToken.cancel()
		},

		async watchPolls() {
			if (this.cancelToken) {
				// there is already a cancelToken, so just cancel the previous session and exit
				this.cancelWatch()
				return
			}

			console.debug('[polls]', 'Watch for updates')
			this.cancelToken = axios.CancelToken.source()

			while (this.retryCounter < this.maxTries) {
				this.updateType = await this.$store.dispatch('appSettings/getUpdateType')

				if (this.updateType === 'noPolling') {
					console.debug('[polls]', 'Polling for updates is disabled')
					break
				}

				try {
					await this.getRoute()

					const response = await axios.get(generateUrl(this.endPoint), {
						params: { offset: this.lastUpdated },
						cancelToken: this.cancelToken.token,
					})

					this.retryCounter = 0 // reset retryCounter after we got a valid response

					console.debug('[polls]', 'update detected', response.data.updates)
					await this.loadTables(response.data.updates)

				} catch (e) {
					if (axios.isCancel(e)) {
						console.debug('[polls]', 'Watch canceled')
					} else if (e.response?.status === 304) {
						await this.handleNotModifiedResponse()
					} else {
						await this.handleConnectionError(e)
						await new Promise((resolve) => setTimeout(resolve, e.name === 'NotReady' ? 2000 : this.retryTimeout))
					}
				}

				if (this.updateType === 'periodicPolling') {
					console.debug('[polls]', 'Sleep for', this.sleepTimeout, 'seconds')
					await this.sleep(this.sleepTimeout)
				}
			}
			// invalidate the cancel token before leaving
			this.cancelToken = null
		},

		sleep(timeout) {
			return new Promise((resolve) => setTimeout(resolve, timeout * 1000))
		},

		async loadTables(tables) {
			let dispatches = []

			tables.forEach((item) => {
				this.lastUpdated = Math.max(item.updated, this.lastUpdated)

				if (item.table === 'polls') {
					if (this.isAdmin) {
						// If user is an admin, also load admin list
						dispatches = [...dispatches, 'pollsAdmin/list']
					}

					if (item.pollId === parseInt(this.$route.params.id ?? this.$store.state.share.pollId)) {
						// if current poll is affected, load current poll configuration
						dispatches = [...dispatches, 'poll/get']
					}

					if (this.isLoggedin) {
						// if user is an authorized user load polls list
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
		},

		getRoute() {
			if (this.$route.name === null) {
				throw new NotReady('Router not initialized')
			}

			if (this.$route.name === 'publicVote') {
				this.endPoint = `apps/polls/s/${this.$route.params.token}/watch`
			} else {
				this.endPoint = `apps/polls/poll/${this.$route.params.id ?? 0}/watch`
			}
		},

		handleNotModifiedResponse() {
			console.debug('[polls]', 'Not modified')
			this.retryCounter = 0 // reset retryCounter, after we get a 304
		},

		handleConnectionError(e) {
			this.retryCounter += 1
			console.debug('[polls]', e.message ?? 'No response - request aborted - failed request', '-', `${this.retryCounter}/${this.maxTries}`)

			if (e.response) {
				console.error('[polls]', 'Unhandled error watching polls', e)
			}

		},

	},
}
