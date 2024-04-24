/* jshint esversion: 6 */
/**
 * @copyright Copyright (c) 2019 Rene Gieling <github@dartcafe.de>
 *
 * @author Rene Gieling <github@dartcafe.de>
 *
 * @author Vinzenz Rosenkranz <vinzenz.rosenkranz@gmail.com>
 *
 * @license  AGPL-3.0-or-later
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

import moment from '@nextcloud/moment'
import { orderBy } from 'lodash'
import { PollsAPI } from '../../Api/index.js'

const state = {
	list: [],
	isPollCreationAllowed: false,
	isComboAllowed: false,
	currentCategoryId: 'all',
	pollsLoading: false,
	sort: {
		by: 'created',
		reverse: true,
	},
	categories: [
		{
			id: 'relevant',
			title: t('polls', 'Relevant'),
			titleExt: t('polls', 'Relevant polls'),
			description: t('polls', 'Relevant polls which are relevant or for you, because you are a participant or the owner or you are invited to.'),
			pinned: false,
			createDependent: false,
			filterCondition(poll) {
				return !poll.deleted
					&& (poll.relevantThreshold > (moment().unix()))
					&& (poll.currentUser.isInvolved
						|| (poll.permissions.allowView && poll.access !== 'open')
					)
			},
		},
		{
			id: 'my',
			title: t('polls', 'My polls'),
			titleExt: t('polls', 'My polls'),
			description: t('polls', 'Your polls (in which you are the owner).'),
			pinned: false,
			createDependent: true,
			filterCondition(poll) {
				return !poll.deleted && poll.currentUser.isOwner
			},
		},
		{
			id: 'private',
			title: t('polls', 'Private polls'),
			titleExt: t('polls', 'Private polls'),
			description: t('polls', 'All private polls, to which you have access.'),
			pinned: false,
			createDependent: true,
			filterCondition(poll) {
				return !poll.deleted && poll.access === 'private'
			},
		},
		{
			id: 'participated',
			title: t('polls', 'Participated'),
			titleExt: t('polls', 'Participated'),
			description: t('polls', 'All polls, where you placed a vote.'),
			pinned: false,
			createDependent: false,
			filterCondition(poll) {
				return !poll.deleted && poll.currentUser.hasVoted
			},
		},
		{
			id: 'open',
			title: t('polls', 'Openly accessible polls'),
			titleExt: t('polls', 'Openly accessible polls'),
			description: t('polls', 'A complete list with all openly accessible polls on this site, regardless who is the owner.'),
			pinned: false,
			createDependent: true,
			filterCondition(poll) {
				return !poll.deleted && poll.access === 'open'
			},
		},
		{
			id: 'all',
			title: t('polls', 'All polls'),
			titleExt: t('polls', 'All polls'),
			description: t('polls', 'All polls, where you have access to.'),
			pinned: false,
			createDependent: false,
			filterCondition(poll) {
				return !poll.deleted
			},
		},
		{
			id: 'closed',
			title: t('polls', 'Closed polls'),
			titleExt: t('polls', 'Closed polls'),
			description: t('polls', 'All closed polls, where voting is disabled.'),
			pinned: false,
			createDependent: false,
			filterCondition(poll) {
				return !poll.deleted
                    && poll.expire > 0
                    && moment.unix(poll.expire).diff() < 0
			},
		},
		{
			id: 'archived',
			title: t('polls', 'Archive'),
			titleExt: t('polls', 'My archived polls'),
			description: t('polls', 'Your archived polls are only accessible to you.'),
			pinned: true,
			createDependent: true,
			filterCondition(poll) {
				return poll.deleted
			},
		},
	],
}

const namespaced = true

const mutations = {
	set(state, payload) {
		Object.assign(state, payload)
	},

	setLoading(state, loading) {
		state.pollsLoading = loading ?? true
	},

	setFilter(state, payload) {
		state.currentCategoryId = payload.currentCategoryId
	},

	setSort(state, payload) {
		if (state.sort.by === payload.sortBy) {
			state.sort.reverse = !state.sort.reverse
		} else {
			state.sort.reverse = true
		}
		state.sort.by = payload.sortBy
	},

	setPollCreationAllowed(state, payload) {
		state.isPollCreationAllowed = payload.pollCreationAllowed
	},
	setComboAllowed(state, payload) {
		state.isComboAllowed = payload.comboAllowed
	},
}

const getters = {
	categories(state) {
		if (state.isPollCreationAllowed) {
			return state.categories
		}
		return state.categories.filter((category) => (!category.createDependent))
	},

	activePolls: (state, getters) => getters.filtered('all'),
	datePolls: (state) => state.list.filter((poll) => (poll.type === 'datePoll' && !poll.deleted)),

	filtered: (state, getters) => (filterId) => {
		const currentCategory = state.categories.find((category) => category.id === filterId)
		return orderBy(
			state.list.filter((poll) => currentCategory.filterCondition(poll)),
			[state.sort.by],
			[state.sort.reverse ? 'desc' : 'asc'],
		)
	},
}

const actions = {
	async setSort(context, payload) {
		context.commit('setSort', { sortBy: payload.sortBy })
	},

	async setFilter(context, payload) {
		context.commit('setFilter', { currentCategoryId: payload })
	},

	async list(context) {

		try {
			context.commit('setLoading')
			const response = await PollsAPI.getPolls()
			context.commit('set', { list: response.data.list })
			context.commit('setPollCreationAllowed', { pollCreationAllowed: response.data.pollCreationAllowed })
			context.commit('setComboAllowed', { comboAllowed: response.data.comboAllowed })
		} catch (e) {
			if (e?.code === 'ERR_CANCELED') return
			console.error('Error loading polls', { error: e.response })
			throw e
		} finally {
			context.commit('setLoading', false)
		}
	},
}

export default { namespaced, state, mutations, getters, actions }
