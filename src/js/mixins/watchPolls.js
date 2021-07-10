
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import exception from '../Exceptions/Exceptions'
import { emit } from '@nextcloud/event-bus'

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
		}
	},

	methods: {
		watchPollsRestart() {
			this.restart = true
			this.cancelToken.cancel()
		},

		async loadTables(tables) {
			let dispatches = []
			tables.forEach((item) => {
				this.lastUpdated = Math.max(item.updated, this.lastUpdated)
				if (item.table === 'polls') {
					emit('update-polls')
					if (item.pollId === parseInt(this.$route.params.id ?? this.$store.state.share.pollId)) {
						// if current poll is affected, load current poll configuration
						dispatches = [...dispatches, 'poll/get']
					}
				} else {
					// a table change of the current poll was reported, load
					// corresponding stores
					dispatches = [...dispatches, item.table + '/list']
				}
			})

			// remove duplicates
			dispatches = [...new Set(dispatches)]
			await Promise.all(dispatches.map((dispatches) => this.$store.dispatch(dispatches)))
		},

		async watchPolls() {
			console.debug('polls', 'Watch for updates')
			this.cancelToken = axios.CancelToken.source()
			this.retryCounter = 0

			while (this.retryCounter < this.maxTries) {
				let endPoint = 'apps/polls'
				if (this.$route.name === 'publicVote') {
					endPoint = endPoint + '/s/' + this.$route.params.token
				} else {
					endPoint = endPoint + '/poll/' + (this.$route.params.id ?? 0)
				}

				try {
					const response = await axios.get(generateUrl(endPoint + '/watch'), {
						params: { offset: this.lastUpdated },
						cancelToken: this.cancelToken.token,
					})

					if (typeof response.data?.updates !== 'object') {
						console.debug('return value is no array')
						throw exception('Invalid content')
					}
					if (this.retryCounter) {
						// timeout happened after connection errors
						this.retryCounter = 0
					} else {
						// If server responds with an HTML-Page like the update page,
						// throw an simple exception
						console.debug('polls', 'update detected', response.data.updates)
						await this.loadTables(response.data.updates)
					}

				} catch (e) {

					if (axios.isCancel(e)) {
						if (this.restart) {
							// Restarting of poll was initiated
							console.debug('watch canceled - restart watch')
							this.retryCounter = 0
							this.restart = false
							this.cancelToken = axios.CancelToken.source()
						} else {
							// request got canceled by a user invention
							// we will exit here
							console.debug('watch canceled')
							return
						}
					} else if (e.response?.status === 304) {
						// the request timed out without updates
						// this is expected --> restart
						if (this.retryCounter) {
							// timeout happened after connection errors
							this.retryCounter = 0
						}
					} else {
						// No response was returned, i.e. server died or exception was triggered
						this.retryCounter += 1
						if (e.response) {
							console.error('Unhandled error watching polls', e)
						}
						console.debug('No response - request aborted - failed request', this.retryCounter)
						await new Promise((resolve) => setTimeout(resolve, this.retryTimeout))
					}
				}
			}
		},
	},
}
