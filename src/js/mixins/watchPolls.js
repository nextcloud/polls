
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import Exception from '../Exceptions/Exceptions'
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
		}
	},

	methods: {
		watchPollsRestart() {
			this.restart = true
			this.cancelToken.cancel()
		},

		async watchPolls() {
			console.debug('[polls]', 'Watch for updates')
			await this.initWatch()

			while (this.retryCounter < this.maxTries) {
				try {
					if (this.$route.name === null) {
						throw new Exception('Router not initialized')
					}
					const response = await axios.get(generateUrl(this.endPoint), {
						params: { offset: this.lastUpdated },
						cancelToken: this.cancelToken.token,
					})

					if (typeof response.data?.updates !== 'object') {
						console.debug('[polls]', 'return value is no array')
						throw new Exception('Invalid content')
					}

					this.retryCounter = 0
					console.debug('[polls]', 'update detected', response.data.updates)
					await this.loadTables(response.data.updates)

				} catch (e) {
					if (axios.isCancel(e)) {
						await this.handleCanceledRequest()
					} else if (e.response?.status === 304) {
						await this.handleNotModifiedResponse()
					} else if (e.message === 'Router not initialized') {
						await this.handleNotModifiedResponse()
						await this.handleConnectionError(e)
						await new Promise((resolve) => setTimeout(resolve, 2000))
					} else {
						// No valid response was returned, i.e. server died or
						// an exception was triggered
						await this.handleConnectionError(e)
						await new Promise((resolve) => setTimeout(resolve, this.retryTimeout))
					}
				}
			}
		},

		async loadTables(tables) {
			let dispatches = []

			tables.forEach((item) => {
				this.lastUpdated = Math.max(item.updated, this.lastUpdated)

				if (item.table === 'polls') {
					if (getCurrentUser().isAdmin) {
						dispatches = [...dispatches, 'pollsAdmin/list'] // If user is an admin, also load admin list
					}

					if (item.pollId === parseInt(this.$route.params.id ?? this.$store.state.share.pollId)) {
						dispatches = [...dispatches, 'poll/get'] // if current poll is affected, load current poll configuration
					}
				}
				dispatches = [...dispatches, item.table + '/list']
			})

			// remove duplicates
			dispatches = [...new Set(dispatches)]
			await Promise.all(dispatches.map((dispatches) => this.$store.dispatch(dispatches)))
		},

		initWatch() {
			this.cancelToken = axios.CancelToken.source()
			this.retryCounter = 0
			if (this.$route.name === 'publicVote') {
				this.endPoint = 'apps/polls/s/' + this.$route.params.token + '/watch'
			} else {
				this.endPoint = 'apps/polls/poll/' + (this.$route.params.id ?? 0) + '/watch'
			}
		},

		handleCanceledRequest() {
			if (this.restart) {
				// Restarting of poll was initiated
				console.debug('[polls]', 'watch canceled - restart watch')
				this.initWatch()
				this.restart = false
			} else {
				// we will exit here
				console.debug('[polls]', 'watch canceled')
				this.retryCounter = this.maxTries
			}
		},

		handleNotModifiedResponse() {
			console.debug('[polls]', 'Not modified')
			this.retryCounter = 0
		},

		handleConnectionError(e) {
			this.retryCounter += 1 // incremet the retry counter

			console.debug('[polls]', e.message ?? 'No response - request aborted - failed request', '-', this.retryCounter + '/' + this.maxTries)

			if (e.response) {
				console.error('[polls]', 'Unhandled error watching polls', e)
			}

		},

	},
}
