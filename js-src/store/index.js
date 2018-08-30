import Vue from 'vue'
import Vuex from 'vuex'
import poll from './modules/poll'
import siteUsers from './modules/siteUsers'
import siteGroups from './modules/siteGroups'

Vue.use(Vuex)

export default new Vuex.Store({
	modules: {
		poll,
		siteUsers,
		siteGroups
	},
	
	state: {
		test: 0,
		poll: {
			mode: 'create',
			comments: [],
			votes: [],
			shares: [],
			event: {
				id: 0,
				hash: '',
				type: 'datePoll',
				title: '',
				description: '',
				owner:'',
				created: '',
				access: 'public',
				expiration: false,
				expire: false,
				expired: false,
				isAnonymous: false,
				fullAnonymous: false,
				disallowMaybe: false,
			},
			options: {
				pollDates: [],
				pollTexts: []
			}
		},
	},
	
	mutations: {
		//
		increment (state) {
			state.test++
		}

	},
	actions: {
		//
	}
})
