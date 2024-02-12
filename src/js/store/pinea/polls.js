/**
 * @copyright Copyright (c) 2024 Rene Gieling <github@dartcafe.de>
 *
 * @author Rene Gieling <github@dartcafe.de>
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

import { defineStore } from 'pinia'
import moment from '@nextcloud/moment'
import { orderBy } from 'lodash'
import { PollsAPI } from '../../Api/index.js'

export const usePollsStore = defineStore('PollsStore', {
	state: () => ({
		list: [],
		isPollCreationAllowed: false,
		isComboAllowed: false,
		currentCategoryId: 'all',
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

	}),
	getters: {
		categories() {
			if (this.isPollCreationAllowed) {
				return this.categories
			}
			return this.categories.filter((category) => (!category.createDependent))
		},

		activePolls() {
			return this.filtered('all')
		},

		datepolls() {
			return this.list.filter((poll) => (poll.type === 'datePoll' && !poll.deleted))
		},

		filtered(filterId) {
			const currentCategory = this.categories.find((category) => category.id === filterId)
			return orderBy(
				this.list.filter((poll) => currentCategory.filterCondition(poll)),
				[this.sort.by],
				[this.sort.reverse ? 'desc' : 'asc'],
			)
		},
	},

	actions: {
		async setSort(payload) {
			if (this.sort.by === payload.sortBy) {
				this.sort.reverse = !this.sort.reverse
			} else {
				this.sort.reverse = true
			}
			this.sort.by = payload.sortBy
		},

		async setFilter(newCategoryId) {
			this.currentCategoryId = newCategoryId
		},

		async list() {

			try {
				const response = await PollsAPI.getPolls()
				this.list = response.data.list
				this.isPollCreationAllowed = response.data.pollCreationAllowed
				this.setComboAllowed = response.data.comboAllowed
			} catch (e) {
				if (e?.code === 'ERR_CANCELED') return
				console.error('Error loading polls', { error: e.response })
				throw e
			}
		},

	},
})
