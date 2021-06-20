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
	categories: [
		{
			id: 'relevant',
			title: t('polls', 'Relevant'),
			titleExt: t('polls', 'Relevant polls'),
			description: t('polls', 'All polls which are relevant or important to you, because you are a participant or the owner or you are invited to. Without polls closed more than five days ago.'),
			icon: 'icon-details',
			pinned: false,
		},
		{
			id: 'my',
			title: t('polls', 'My polls'),
			titleExt: t('polls', 'My polls'),
			description: t('polls', 'Your polls (in which you are the owner).'),
			icon: 'icon-user',
			pinned: false,
		},
		{
			id: 'hidden',
			title: t('polls', 'Hidden polls'),
			titleExt: t('polls', 'Hidden polls'),
			description: t('polls', 'All hidden polls, to which you have access.'),
			icon: 'icon-polls-hidden-poll',
			pinned: false,
		},
		{
			id: 'participated',
			title: t('polls', 'Participated'),
			titleExt: t('polls', 'Participated'),
			description: t('polls', 'All polls, where you placed a vote.'),
			icon: 'icon-polls-confirmed',
			pinned: false,
		},
		{
			id: 'public',
			title: t('polls', 'Public polls'),
			titleExt: t('polls', 'Public polls'),
			description: t('polls', 'A complete list with all public polls on this site, regardless who is the owner.'),
			icon: 'icon-link',
			pinned: false,
		},
		{
			id: 'all',
			title: t('polls', 'All polls'),
			titleExt: t('polls', 'All polls'),
			description: t('polls', 'All polls, where you have access to.'),
			icon: 'icon-polls',
			pinned: false,
		},
		{
			id: 'closed',
			title: t('polls', 'Closed polls'),
			titleExt: t('polls', 'Closed polls'),
			description: t('polls', 'All closed polls, where voting is disabled.'),
			icon: 'icon-polls-closed',
			pinned: false,
		},
		{
			id: 'archived',
			title: t('polls', 'Archive'),
			titleExt: t('polls', 'My archived polls'),
			description: t('polls', 'Your archived polls are only accessible to you.'),
			icon: 'icon-category-app-bundles',
			pinned: true,
		},
	],
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
			return state.list.filter((poll) => (!poll.deleted))
		} else if (filterId === 'my') {
			return state.list.filter((poll) => (poll.owner === getCurrentUser().uid && !poll.deleted))
		} else if (filterId === 'relevant') {
			return state.list.filter((poll) => ((
				poll.important
				|| poll.userHasVoted
				|| poll.isOwner
				|| (poll.allowView && poll.access !== 'public')
			)
			&& !poll.deleted
			&& !(poll.expire > 0 && moment.unix(poll.expire).diff(moment(), 'days') < -4)
			))
		} else if (filterId === 'public') {
			return state.list.filter((poll) => (poll.access === 'public' && !poll.deleted))
		} else if (filterId === 'hidden') {
			return state.list.filter((poll) => (poll.access === 'hidden' && !poll.deleted))
		} else if (filterId === 'archived') {
			return state.list.filter((poll) => (poll.deleted))
		} else if (filterId === 'participated') {
			return state.list.filter((poll) => (poll.userHasVoted))
		} else if (filterId === 'closed') {
			return state.list.filter((poll) => (
				poll.expire > 0 && moment.unix(poll.expire).diff() < 0 && !poll.deleted
			))
		}
	},
}

const actions = {
	async list(context) {
		const endPoint = 'apps/polls/polls'

		try {
			const response = await axios.get(generateUrl(endPoint), { params: { time: +new Date() } })
			context.commit('set', { list: response.data })
		} catch (e) {
			console.error('Error loading polls', { error: e.response })
		}
	},
}

export default { namespaced, state, mutations, getters, actions }
