
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export const watchPolls = {
	data() {
		return {
			cancelToken: null,
			restart: false,
			watching: true,
			lastUpdated: Math.round(Date.now() / 1000),
		}
	},

	methods: {
		watchPollsRestart() {
			this.restart = true
			this.cancelToken.cancel()
		},

		async watchPolls() {
			console.debug('polls', 'Watch for updates')
			this.cancelToken = axios.CancelToken.source()

			while (this.watching) {
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
					let dispatches = []

					console.debug('polls', 'update detected', response.data.updates)

					response.data.updates.forEach((item) => {
						this.lastUpdated = (item.updated > this.lastUpdated) ? item.updated : this.lastUpdated
						// an updated poll table is reported
						if (item.table === 'polls') {
							if (this.$route.name !== 'publicVote') {
								// load poll list only, when not in public poll
								dispatches.push('polls/load')
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

					this.watching = true

				} catch (e) {
					this.watching = false

					if (axios.isCancel(e)) {
						if (this.restart) {
							console.debug('restart watch')
							this.watching = true
							this.restart = false
							this.cancelToken = axios.CancelToken.source()
						} else {
							console.debug('Watch canceled')
							this.watching = true
							this.restart = false
							return
						}
					} else if (e.response) {

						if (e.response.status === 304) {
							this.watching = true
							continue
						} else if (e.response.status === 503) {
							console.debug('Server not available, reconnect watch in 30 sec')

							await new Promise(resolve => setTimeout(resolve, 30000))
							this.watching = true
							continue
						} else {
							console.error('Unhandled error watching polls', e)
							return
						}
					} else if (e.request) {
						console.debug('Watch aborted')
						this.watching = true
						return
					}
				}
			}
		},
	},
}
