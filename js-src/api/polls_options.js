import mocks from './mock.js'
const _mock = true

function loadAllOptionsWrapper() {
	if (_mock) {
		var loadedOptions = _poll_options
		console.log('Loading options with mock data')
		console.log(_poll_options)
	} else {
		// db call here
	}
	return loadedOptions
}

export default {
	getOptions (cb) {
		cb(loadAllOptionsWrapper())
	},

	getOptionsByPollId (cb, query) {
		console.log(query)
		cb(loadAllOptionsWrapper().filter(option => option.poll_id === query))
	}
}
