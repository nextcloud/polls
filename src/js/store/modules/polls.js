/*
 * @copyright Copyright (c) 2019 Rene Gieling <github@dartcafe.de>
 *
 * @author Rene Gieling <github@dartcafe.de>
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import axios from '@nextcloud/axios'

const state = {
	list: []
}

const mutations = {
	setPolls(state, { list }) {
		state.list = list
	}
}

const getters = {
	countPolls: (state) => {
		return state.list.length
	},
	allPolls: (state) => {
		return state.list.filter(poll => (!poll.deleted))
	},
	myPolls: (state) => {
		return state.list.filter(poll => (poll.owner === OC.getCurrentUser().uid && !poll.deleted))
	},
	publicPolls: (state) => {
		return state.list.filter(poll => (poll.access === 'public' && !poll.deleted))
	},
	hiddenPolls: (state) => {
		return state.list.filter(poll => (poll.access === 'hidden' && !poll.deleted))
	},
	deletedPolls: (state) => {
		return state.list.filter(poll => (poll.deleted))
	},
	participatedPolls: (state) => {
		return state.list.filter(poll => (poll.userHasVoted))
	}
}

const actions = {
	loadPolls(context) {
		const endPoint = 'apps/polls/polls/list/'

		return axios.get(OC.generateUrl(endPoint))
			.then((response) => {
				context.commit('setPolls', { list: response.data })
			}, (error) => {
				OC.Notification.showTemporary(t('polls', 'Error loading polls'), { type: 'error' })
				console.error('Error loading polls', { error: error.response })
			})
	},

	switchDeleted(context, payload) {
		const endPoint = 'apps/polls/polls/delete/'
		return axios.get(OC.generateUrl(endPoint + payload.pollId))
			.then((response) => {
				return response
			}, (error) => {
				OC.Notification.showTemporary(t('polls', 'Error deleting poll.'), { type: 'error' })
				console.error('Error deleting poll', { error: error.response }, { payload: payload })
			})
	},

	deletePermanently(context, payload) {
		const endPoint = 'apps/polls/polls/delete/permanent/'
		return axios.get(OC.generateUrl(endPoint + payload.pollId))
			.then((response) => {
                OC.Notification.showTemporary(t('polls', 'Deleted poll permanently.'), { type: 'success' })
				return response
			}, (error) => {
				OC.Notification.showTemporary(t('polls', 'Error deleting poll.'), { type: 'error' })
				console.error('Error deleting poll', { error: error.response }, { payload: payload })
			})
	},

	clonePoll(context, payload) {
		const endPoint = 'apps/polls/polls/clone/'
		return axios.get(OC.generateUrl(endPoint + payload.pollId))
			.then((response) => {
				return response.data
			}, (error) => {
				OC.Notification.showTemporary(t('polls', 'Error cloning poll.'), { type: 'error' })
				console.error('Error cloning poll', { error: error.response }, { payload: payload })
			})

	}

}

export default { state, mutations, getters, actions }
