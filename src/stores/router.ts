/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'

/**
 * Router store
 * TODO: remove this store and replace with injected router
 * Temporary store to hold the current route
 * currently there are problems with the reactivcity of the router, if injected into the stores
 * @return {object} router store
 */
export const useRouterStore = defineStore('router', {
	state: () => ({
		currentRoute: null,
		name: '',
		path: '',
		params: {
			id: 0,
			token: '',
		}
	}),

	actions: {
		async set(payload) {
			this.$patch(payload)
		},
	},
})
