import mocks from './mock.js'
const _mock = true

function loadAllCommentsWrapper() {
	if (_mock) {
		var loadedComments = _poll_comments
		console.log('Loading Comments with mock data')
	} else {
		// db call here
	}
	return loadedComments
}

export default {
	getComments (cb) {
		cb(loadAllCommentsWrapper())
	},

	getCommentsByPollId (cb, query) {
		cb(loadAllCommentsWrapper().filter(comment => comment.poll_id === query))
	}
	
}
