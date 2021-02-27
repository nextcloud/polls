
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export const watchPolls = {
	methods: {
		async watchPolls() {
			console.debug('polls', 'Watch for updates')

			this.cancelToken = axios.CancelToken.source()
			let endPoint = 'apps/polls'

			if (this.$route.name === 'publicVote') {
				endPoint = endPoint + '/s/' + this.$route.params.token
			} else if (this.$route.name === 'vote') {
				endPoint = endPoint + '/poll/' + this.$route.params.id
			} else {
				this.watching = false
			}

			while (this.watching) {
				try {
					const response = await axios.get(generateUrl(endPoint + '/watch'), {
						params: { offset: this.lastUpdated },
						cancelToken: this.cancelToken.token,
					})
					const dispatches = []

					console.debug('polls', 'update detected', response.data.updates)

					response.data.updates.forEach((item) => {
						this.lastUpdated = (item.updated > this.lastUpdated) ? item.updated : this.lastUpdated
						if (item.table === 'polls') {
							dispatches.push('poll/get')
						} else {
							dispatches.push(item.table + '/list')
						}
					})
					const requests = dispatches.map(dispatches => this.$store.dispatch(dispatches))
					await Promise.all(requests)

					this.watching = true

				} catch (error) {
					this.watching = false

					if (axios.isCancel(error)) {
						console.debug('Watch canceld')
					} else if (error.response) {

						if (error.response.status === 304) {

							this.watching = true

						} else if (error.response.status === 503) {

							console.debug('Server not available, reconnect watch in 30 sec')

							await new Promise(resolve => setTimeout(resolve, 30000))
							this.watching = true

						} else {

							console.error('Unhandled error watching polls', error)

						}
					} else if (error.request) {

						console.debug('Watch aborted')
						this.watching = true

					}
				}
			}
		},
	},
}
