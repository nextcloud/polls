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
import { getCurrentUser } from '@nextcloud/auth'
import moment from '@nextcloud/moment'
import { generateUrl } from '@nextcloud/router'

const state = {
	list: [],
}

const namespaced = true

const mutations = {
	set(state, payload) {
		Object.assign(state, payload)
	},
}

const getters = {
	filtered: (state) => (filterId) => {
		if (filterId === 'all') {
			return state.list.filter(poll => (!poll.deleted))
		} else if (filterId === 'my') {
			return state.list.filter(poll => (poll.owner === getCurrentUser().uid && !poll.deleted))
		} else if (filterId === 'relevant') {
			return state.list.filter(poll => ((
				poll.important
				|| poll.userHasVoted
				|| poll.isOwner
				|| (poll.allowView && poll.access !== 'public')
			)
			&& !poll.deleted
			&& !(poll.expire > 0 && moment.unix(poll.expire).diff() < 0)
			))
		} else if (filterId === 'public') {
			return state.list.filter(poll => (poll.access === 'public' && !poll.deleted))
		} else if (filterId === 'hidden') {
			return state.list.filter(poll => (poll.access === 'hidden' && !poll.deleted))
		} else if (filterId === 'deleted') {
			return state.list.filter(poll => (poll.deleted))
		} else if (filterId === 'participated') {
			return state.list.filter(poll => (poll.userHasVoted))
		} else if (filterId === 'expired') {
			return state.list.filter(poll => (
				poll.expire > 0 && moment.unix(poll.expire).diff() < 0 && !poll.deleted
			))
		}
	},
}

const actions = {
	load(context) {
		const endPoint = 'apps/polls/polls/list'

		return axios.get(generateUrl(endPoint))
			.then((response) => {
				context.commit('set', { list: response.data })
			})
			.catch((error) => {
				console.error('Error loading polls', { error: error.response })
			})
	},
}

export default { namespaced, state, mutations, getters, actions }
