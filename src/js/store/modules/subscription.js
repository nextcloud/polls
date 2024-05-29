/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { PollsAPI, PublicAPI } from '../../Api/index.js'
import { Logger } from '../../helpers/index.js'

const defaultSubscription = () => ({
	subscribed: false,
})

const namespaced = true
const state = defaultSubscription()

const mutations = {

	set(state, payload) {
		state.subscribed = payload.subscribed
	},

	reset(state) {
		Object.assign(state, defaultSubscription())
	},

}

const actions = {

	async get(context) {
		try {
			let response = null
			if (context.rootState.route.name === 'publicVote') {
				response = await PublicAPI.getSubscription(context.rootState.route.params.token)
			} else if (context.rootState.route.name === 'vote') {
				response = await PollsAPI.getSubscription(context.rootState.route.params.id)
			} else {
				context.commit('reset')
				return
			}
			context.commit('set', response.data)
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			context.commit('set', false)
			throw error
		}
	},

	async update(context) {
		try {
			let response = null
			if (context.rootState.route.name === 'publicVote') {
				response = await PublicAPI.setSubscription(context.rootState.route.params.token, !context.state.subscribed)
			} else if (context.rootState.route.name === 'vote') {
				response = await PollsAPI.setSubscription(context.rootState.route.params.id, !context.state.subscribed)
			} else {
				context.commit('reset')
				return
			}
			context.commit('set', response.data)
		} catch (error) {
			if (error?.code === 'ERR_CANCELED') return
			Logger.error('Error on changing subscription' , error)
			throw error
		}
	},
}

export default { namespaced, state, mutations, actions }
