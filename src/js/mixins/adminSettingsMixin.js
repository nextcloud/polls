/* jshint esversion: 6 */
/**
 * @copyright Copyright (c) 2021 René Gieling <github@dartcafe.de>
 *
 * @author René Gieling <github@dartcafe.de>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

import { debounce } from 'lodash'
import { mapState } from 'vuex'
import { AppSettingsAPI } from '../Api/index.js'

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
			} catch (e) {
				if (e?.code === 'ERR_CANCELED') return
				console.error(e.response)
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
