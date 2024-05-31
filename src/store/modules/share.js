/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { PublicAPI } from '../../Api/index.js'
import { Logger } from '../../helpers/index.js'

const defaultShares = () => ({
	displayName: '',
	id: null,
	invitationSent: 0,
	locked: 0,
	pollId: null,
	token: '',
	type: '',
	emailAddress: '',
	userId: '',
	publicPollEmail: 'optional',
	user: {
		type: '',
		displayName: '',
		userId: '',
		emailAddress: '',
		isNoUser: false,
	},
})

const namespaced = true
const state = defaultShares()

const mutations = {
	set(state, payload) {
		Object.assign(state, payload.share)
	},

	setEmailAddress(state, payload) {
		state.user.emailAddress = payload
	},

	reset(state) {
		Object.assign(state, defaultShares())
	},
}

const actions = {
	async get(context) {
		if (context.rootState.route.name !== 'publicVote') {
			context.commit('reset')
			return
		}

		try {
			const response = await PublicAPI.getShare(context.rootState.route.params.token)
			context.commit('set', { share: response.data.share })
			return response.data
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.debug('Error retrieving share', { error })
			throw error
		}
	},

	async updateEmailAddress(context, payload) {
		if (context.rootState.route.name !== 'publicVote') {
			return
		}

		try {
			const response = await PublicAPI.setEmailAddress(context.rootState.route.params.token, payload.emailAddress)
			context.commit('set', { share: response.data.share })
			context.dispatch('poll/get', null, { root: true })
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error writing email address', { error, payload })
			throw error
		}
	},

	async updateDisplayName(context, payload) {
		if (context.rootState.route.name !== 'publicVote') {
			return
		}

		try {
			const response = await PublicAPI.setDisplayName(context.rootState.route.params.token, payload.displayName)
			context.commit('set', { share: response.data.share })
			context.dispatch('poll/get', null, { root: true })
			context.dispatch('comments/list', null, { root: true })
			context.dispatch('votes/list', null, { root: true })
			context.dispatch('options/list', null, { root: true })
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error changing name', { error, payload })
			throw error
		}
	},

	async deleteEmailAddress(context, payload) {
		if (context.rootState.route.name !== 'publicVote') {
			return
		}

		try {
			const response = await PublicAPI.deleteEmailAddress(context.rootState.route.params.token)
			context.commit('set', { share: response.data.share })
			context.dispatch('subscription/update', false, { root: true })
			context.dispatch('poll/get', null, { root: true })
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error writing email address', { error, payload })
			throw error
		}
	},

	async resendInvitation(context, payload) {
		if (context.rootState.route.name !== 'publicVote') {
			return
		}

		try {
			return await PublicAPI.resendInvitation(context.rootState.route.params.token)
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error sending invitation', { error, payload })
			throw error
		}
	},
}

export default { namespaced, state, mutations, actions }
