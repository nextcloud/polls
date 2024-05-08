/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { ActivityAPI } from '../../Api/index.js'

const defaultActivities = () => ({
	list: [],
})

const namespaced = true
const state = defaultActivities()

const mutations = {
	set(state, payload) {
		state.list = payload
	},

	reset(state) {
		Object.assign(state, defaultActivities())
	},

	deleteActivities(state, payload) {
		state.list = state.list.filter((vote) => vote.user.userId !== payload.userId)
	},

}

const actions = {
	async list(context) {

		try {
			const response = await ActivityAPI.getActivities(context.rootState.route.params.id)
			context.commit('set', response.data.ocs.data)
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			context.commit('reset')
		}
	},

}

export default { namespaced, state, mutations, actions }
