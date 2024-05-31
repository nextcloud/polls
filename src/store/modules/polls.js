/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import moment from '@nextcloud/moment'
import { orderBy } from 'lodash'
import { PollsAPI } from '../../Api/index.js'
import { Logger } from '../../helpers/index.js'
import { t } from '@nextcloud/l10n'

const filterRelevantCondition = (poll, relevantOffset) => !poll.status.deleted
	&& moment().diff(moment.unix(poll.status.relevantThreshold), 'days') < relevantOffset
	&& (poll.currentUserStatus.isInvolved || (poll.permissions.view && poll.configuration.access !== 'open'))

const filterMyPolls = (poll) => !poll.status.deleted && poll.currentUserStatus.isOwner
const filterPrivatePolls = (poll) => !poll.status.deleted && poll.configuration.access === 'private'
const filterParticipatedPolls = (poll) => !poll.status.deleted && poll.currentUserStatus.countVotes > 0
const filterOpenPolls = (poll) => !poll.status.deleted && poll.configuration.access === 'open'
const filterAllPolls = (poll) => !poll.status.deleted
const filterClosedPolls = (poll) => !poll.status.deleted && poll.configuration.expire && moment.unix(poll.configuration.expire).diff() < 0
const filterArchivedPolls = (poll) => poll.status.deleted

		
const state = {
	list: [],
	meta: {
		currentCategoryId: 'relevant',
		chunksize: 20,
		loadedChunks: 1,
		maxPollsInNavigation: 6,
		permissions: {
			pollCreationAllowed: false,
			comboAllowed: false,
		},
	},
	status: {
		loading: false
	},

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
			filterCondition: (poll, relevantOffset) => filterRelevantCondition(poll, relevantOffset),
		},
		{
			id: 'my',
			title: t('polls', 'My polls'),
			titleExt: t('polls', 'My polls'),
			description: t('polls', 'Your polls (in which you are the owner).'),
			pinned: false,
			createDependent: true,
			filterCondition: (poll) => filterMyPolls(poll),
		},
		{
			id: 'private',
			title: t('polls', 'Private polls'),
			titleExt: t('polls', 'Private polls'),
			description: t('polls', 'All private polls, to which you have access.'),
			pinned: false,
			createDependent: true,
			filterCondition: (poll) => filterPrivatePolls(poll),
		},
		{
			id: 'participated',
			title: t('polls', 'Participated'),
			titleExt: t('polls', 'Participated'),
			description: t('polls', 'All polls, where you placed a vote.'),
			pinned: false,
			createDependent: false,
			filterCondition: (poll) => filterParticipatedPolls(poll),
		},
		{
			id: 'open',
			title: t('polls', 'Openly accessible polls'),
			titleExt: t('polls', 'Openly accessible polls'),
			description: t('polls', 'A complete list with all openly accessible polls on this site, regardless who is the owner.'),
			pinned: false,
			createDependent: true,
			filterCondition: (poll) => filterOpenPolls(poll),
		},
		{
			id: 'all',
			title: t('polls', 'All polls'),
			titleExt: t('polls', 'All polls'),
			description: t('polls', 'All polls, where you have access to.'),
			pinned: false,
			createDependent: false,
			filterCondition: (poll) => filterAllPolls(poll),
		},
		{
			id: 'closed',
			title: t('polls', 'Closed polls'),
			titleExt: t('polls', 'Closed polls'),
			description: t('polls', 'All closed polls, where voting is disabled.'),
			pinned: false,
			createDependent: false,
			filterCondition: (poll) => filterClosedPolls(poll),
		},
		{
			id: 'archived',
			title: t('polls', 'Archive'),
			titleExt: t('polls', 'My archived polls'),
			description: t('polls', 'Your archived polls are only accessible to you.'),
			pinned: true,
			createDependent: true,
			filterCondition: (poll) => filterArchivedPolls(poll),
		},
	],
}

const sortColumnsMapping = {
	created: 'status.created',
	title: 'configuration.title',
	access: 'configuration.access',
	owner: 'owner.displayName',
	expire: 'configuration.expire',
}

const namespaced = true

const mutations = {
	set(state, payload) {
		Object.assign(state, payload)
	},

	setLoading(state, loading) {
		state.status.loading = loading ?? true
	},

	addChunk(state) {
		state.meta.loadedChunks = state.meta.loadedChunks + 1
	},

	resetChunks(state) {
		state.meta.loadedChunks = 1
	},

	setFilter(state, payload) {
		state.meta.currentCategoryId = payload.currentCategoryId
	},

	setSort(state, payload) {
		if (state.sort.by === sortColumnsMapping[payload.sortBy]) {
			state.sort.reverse = !state.sort.reverse
		} else {
			state.sort.reverse = true
		}
		state.sort.by = sortColumnsMapping[payload.sortBy]
	},

	setPollsPermissions(state, payload) {
		state.meta.permissions = payload.permissions
	},
}

const getters = {
	categories(state) {
		if (state.meta.permissions.pollCreationAllowed) {
			return state.categories
		}
		return state.categories.filter((category) => (!category.createDependent))
	},
	// activePolls: (state, getters) => getters.filtered('all').slice(0, getters.loaded),
	count: (state, getters) => getters.filteredRaw.length,
	loaded: (state) => state.meta.loadedChunks * state.meta.chunksize,
	datePolls: (state) => state.list.filter((poll) => (poll.type === 'datePoll' && !poll.status.deleted)),
	currentCategory: (state) => state.categories.find((category) => category.id === state.meta.currentCategoryId),

	filteredRaw: (state, getters, rootState) => orderBy(
		state.list.filter((poll) => getters.currentCategory.filterCondition(poll, rootState.settings.user.relevantOffset)),
		[state.sort.by],
		[state.sort.reverse ? 'desc' : 'asc'],
	),

	filtered: (state, getters) => getters.filteredRaw.slice(0, getters.loaded),

	countByCategory: (state, getters, rootState) => (filterId) => state.list.filter((poll) => state.categories.find((category) => category.id === filterId).filterCondition(poll, rootState.settings.user.relevantOffset)).length,
	filteredByCategory: (state, getters, rootState) => (filterId) => {
		const currentCategory = state.categories.find((category) => category.id === filterId)
		return orderBy(
			state.list.filter((poll) => currentCategory.filterCondition(poll, rootState.settings.user.relevantOffset)),
			[state.sort.by],
			[state.sort.reverse ? 'desc' : 'asc'],
		).slice(0, state.meta.maxPollsInNavigation)
	},

}

const actions = {
	async setSort(context, payload) {
		context.commit('setSort', { sortBy: payload.sortBy })
	},

	async setFilter(context, payload) {
		context.commit('setFilter', { currentCategoryId: payload })
		context.commit('resetChunks')
	},

	async list(context) {
		try {
			context.commit('setLoading')
			const response = await PollsAPI.getPolls()
			context.commit('set', { list: response.data.list })
			context.commit('setPollsPermissions', { permissions: response.data.permissions })
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error loading polls', { error })
			throw error
		} finally {
			context.commit('setLoading', false)
		}
	},
}

export default { namespaced, state, mutations, getters, actions }
