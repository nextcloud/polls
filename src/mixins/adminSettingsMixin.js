/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { debounce } from 'lodash'
import { mapState } from 'vuex'
import { AppSettingsAPI } from '../Api/index.js'
import { Logger } from '../helpers/index.js'


export const loadGroups = {
	data() {
		return {
			searchToken: null,
			groups: [],
			isLoading: false,
		}
	},

	computed: {
		...mapState({
			appSettings: (state) => state.appSettings,
		}),
	},

	created() {
		this.loadGroups('')
	},

	methods: {
		loadGroups: debounce(async function(query) {
			this.isLoading = true

			try {
				const response = await AppSettingsAPI.getGroups(query)
				this.groups = response.data.groups
				this.isLoading = false
			} catch (error) {
				if (error?.code === 'ERR_CANCELED') return
				Logger.error('Error getting groups' , { error: error.response})
				this.isLoading = false
			}
		}, 250),
	},
}

export const writeValue = {
	computed: {
		...mapState({
			appSettings: (state) => state.appSettings,
		}),
	},

	methods: {
		async writeValue(value) {
			await this.$store.commit('appSettings/set', value)
			this.$store.dispatch('appSettings/write')
		},
	},
}
