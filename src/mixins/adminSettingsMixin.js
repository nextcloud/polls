/* jshint esversion: 6 */
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { debounce } from 'lodash'
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
