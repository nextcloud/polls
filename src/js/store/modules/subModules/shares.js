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
import { generateUrl } from '@nextcloud/router'

const defaultShares = () => {
	return {
		list: [],
	}
}

const state = defaultShares()

const namespaced = true

const mutations = {
	set(state, payload) {
		state.list = payload.shares
	},

	reset(state) {
		Object.assign(state, defaultShares())
	},

}

const getters = {
	invitation: state => {
		const invitationTypes = ['user', 'group', 'email', 'external', 'contact']
		return state.list.filter(share => {
			return invitationTypes.includes(share.type)
		})
	},

	public: state => {
		const invitationTypes = ['public']
		return state.list.filter(share => {
			return invitationTypes.includes(share.type)
		})
	},

}

const actions = {
	add(context, payload) {
		const endPoint = 'apps/polls/share/add/'
		payload.share.pollId = context.rootState.poll.id
		return axios.post(generateUrl(endPoint), { pollId: context.rootState.poll.id, share: payload.share })
			.then((response) => {
				context.commit('set', { shares: response.data.shares })

				if (response.data.sendResult.sentMails.length > 0) {
					const sendList = response.data.sendResult.sentMails.map(element => {

						if (element.displayName) {
							return element.displayName
						} else if (element.userId) {
							return element.userId
						} else if (element.eMailAddress) {
							return element.eMailAddress
						}
					})
					OC.Notification.showTemporary(t('polls', 'Invitation mail sent to %n.', 1, sendList.join(', ')), { type: 'success' })
				}

				if (response.data.sendResult.abortedMails.length > 0) {
					const errorList = response.data.sendResult.abortedMails.map(element => {
						if (element.displayName) {
							return element.displayName
						} else if (element.userId) {
							return element.userId
						} else if (element.eMailAddress) {
							return element.eMailAddress
						}
					})
					OC.Notification.showTemporary(t('polls', 'Error sending invitation mail to %n.', 1, errorList.join(', ')), { type: 'error' })
				}
				return response.data
			}, (error) => {
				console.error('Error writing share', { error: error.response }, { payload: payload })
				throw error
			})
	},

	remove(context, payload) {
		const endPoint = 'apps/polls/share/remove/'
		return axios.post(generateUrl(endPoint), { share: payload.share })
			.then((response) => {
				context.commit('set', { shares: response.data.shares })
			}, (error) => {
				console.error('Error removing share', { error: error.response }, { payload: payload })
				throw error
			})
	},

	addPersonal(context, payload) {
		const endPoint = 'apps/polls/share/create/s/'

		return axios.post(generateUrl(endPoint), { token: payload.token, userName: payload.userName })
			.then((response) => {
				return { token: response.data.token }
			}, (error) => {
				console.error('Error writing share', { error: error.response }, { payload: payload })
				throw error
			})

	},
}

export default { namespaced, state, mutations, actions, getters }
