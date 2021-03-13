
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

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
				// an updated poll table is reported
				if (item.table === 'polls') {
					if (this.$route.name !== 'publicVote') {
						// load poll list only, when not in public poll
						dispatches.push('polls/list')
					}
					if (item.pollId === parseInt(this.$route.params.id ?? this.$store.state.share.pollId)) {
						// if current poll is affected, load current poll configuration
						dispatches.push('poll/get')
						// load also options and votes
						dispatches.push('votes/list')
						dispatches.push('options/list')
					}
				} else if (['votes', 'options'].includes(item.table)) {
					dispatches.push('votes/list')
					dispatches.push('options/list')
				} else {
					// a table of the current poll was reported, load
					// corresponding stores
					dispatches.push(item.table + '/list')
				}
			})
			// remove duplicates
			dispatches = [...new Set(dispatches)]
			// execute all loads within one promise
			const requests = dispatches.map(dispatches => this.$store.dispatch(dispatches))
			await Promise.all(requests)
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
					console.debug('polls', 'update detected', response.data.updates)
					this.retryCounter = 0
					await this.loadTables(response.data.updates)

				} catch (e) {

					if (axios.isCancel(e)) {
						if (this.restart) {
							console.debug('restart watch')
							this.retryCounter = 0
							this.restart = false
							this.cancelToken = axios.CancelToken.source()
						} else {
							console.debug('Watch canceled')
							return
						}
					} else if (e.response) {
						if (e.response.status === 304) {
							// timeout of poll --> restart
							this.retryCounter = 0
						} else {
							this.retryCounter++
							console.error('Unhandled error watching polls', e)
							console.debug('error request', this.retryCounter)
							await new Promise(resolve => setTimeout(resolve, this.retryTimeout))
						}
					} else if (e.request) {
						this.retryCounter++
						console.debug('No response - request aborted')
						console.debug('failed request', this.retryCounter)
						await new Promise(resolve => setTimeout(resolve, this.retryTimeout))
					}
				}
			}
		},
	},
}
