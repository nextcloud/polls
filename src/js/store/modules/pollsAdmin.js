/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { getCurrentUser } from '@nextcloud/auth'
import { PollsAPI } from '../../Api/index.js'
import { Logger } from '../../helpers/index.js'

const namespaced = true
const state = {
	list: [],
}

const mutations = {
	set(state, payload) {
		Object.assign(state, payload)
	},
}

const getters = {
	filtered: (state) => (filterId) => state.list,
}

const actions = {
	async list(context) {
		if (!getCurrentUser().isAdmin) {
			return
		}

		try {
			const response = await PollsAPI.getPollsForAdmin()
			context.commit('set', { list: response.data })
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error loading polls', { error })
			throw error
		}
	},

	async takeOver(context, payload) {
		if (!getCurrentUser().isAdmin) {
			return
		}

		try {
			await PollsAPI.takeOver(payload.pollId)
			context.dispatch('list')
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			throw error
		}
	},
}

export default { namespaced, state, mutations, getters, actions }
