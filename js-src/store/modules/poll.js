import moment from 'moment';
import ApiPolls from '../../api/polls_events'

// initial state
const state = {
	events: [],
	currentPoll: {
		mode: 'create',
		comments: [],
		votes: [],
		shares: [],
		options: [],
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
		}
    }
}

// getters
const getters = {}

// actions
const actions = {

	getPolls ({ commit }) {
		ApiPolls.getEvents(events => {
			commit('setPollsList', events)
		})
	  },

	getPollByHash ({ commit }, hash) {
		ApiPolls.getEvents(event => {
			commit('setPoll', event)
		}, hash)

		ApiPolls.getOptions(options => {
			commit('setOptionsOfEvent', options)
		}, state.currentPoll.event.id)
		
		ApiPolls.getComments(comments => {
			commit('setCommentsOfEvent', comments)
		}, state.currentPoll.event.id)

		ApiPolls.getVotes(votes => {
			commit('setVotesOfEvent', votes)
		}, state.currentPoll.event.id)

		ApiPolls.getShares(shares => {
			commit('setSharesOfEvent', shares)
		}, state.currentPoll.event.id)
	},
}

// mutations
const mutations = {
	setPollsList (state, events) {
		state.events = events
	},
	
	setVotesOfEvent (state, votes) {
		state.currentPoll.votes = votes
	},
	
	setOptionsOfEvent (state, options) {
		state.currentPoll.options = options
	},
	
	setCommentsOfEvent (state, comments) {
		state.currentPoll.comments = comments
	},
	
	setSharesOfEvent (state, shares) {
		state.currentPoll.shares = shares
	},
	
	setPoll (state, event) {
		
		// if (('|public|hidden|registered').indexOf(event.access) < 0) {
			// state.currentPoll.shares = event.access.split(";")
			// state.shareList = event.access.split(";").filter(share => share !== '')
			// state.shareList2 = state.shareList.forEach(share => share.split("_"))
			// event.access = 'shared'
		// }
		
		
		
		state.currentPoll = {
			'result' : 'found',
			'mode' : (event.owner === OC.getCurrentUser().uid ? 'edit' : 'create'),
			'comments' : [], // $commentsList,
			'votes' : [], //$votesList,
			'shares' : [], // (event.access === 'shared' ? state.shareList : []),
			'event' : event,
			'options' : {
				'pollDates' : [],
				'pollTexts' : [] // $optionList
			}
		}
	}

}

export default {
  namespaced: true,
  state,
  getters,
  actions,
  mutations
}
