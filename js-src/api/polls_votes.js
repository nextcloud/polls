import mocks from './mock.js'
const _mock = true

function loadAllVotesWrapper() {
	if (_mock) {
		var loadedVotes = _poll_votes
		console.log('Loading votes with mock data')
	} else {
		// db call here
	}
	return loadedVotes
}

export default {
	getVotes (cb) {
		cb(loadAllVotesWrapper())
	},

	getVotesByPollId (cb, query) {
		cb(loadAllVotesWrapper().filter(vote => vote.poll_id === query))
	}
}
