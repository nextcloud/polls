
import axios from 'axios'
const _route = 'apps/polls/get/shares/'
import mocks from './mock.js'
const _mock = true

function transformShares(shares) {

	for (i = 0; i < shares.length; i++) {
		console.log('shares.length: ' + shares.length)
		console.log('shares[' + i + '].access: ' + shares[i].access)
		if (('|public|hidden|registered').indexOf(shares[i].access) < 0) {
			shares[i] = shares[i].access.split(";").filter(access => access !== '').map(element => {
				return {
					'id' : i,
					'type' : element.split("_")[0],
					'uid' : element.split("_")[1],
					'displayName' : '',
					'avatarUrl' : '',
					'hash' : ''
				}
			})
		}
	}
	return shares
}

function loadAllSharesWrapper() {
	if (_mock) {
		console.log('Loading shares with mock data')
		return transformShares(_poll_events)
	} else {
		return axios.get(OC.generateUrl(_route))
		.then((response) => {
			return transformShares(response.data)
		}, (error) => {
		})	
	}
}

export default {
	getSharesByPollId (cb, query) {
		cb(loadAllSharesWrapper().filter(Shares => Shares.poll_id === query))
	}
  
}
