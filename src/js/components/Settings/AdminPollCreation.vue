<!--
  - @copyright Copyright (c) 2018 René Gieling <github@dartcafe.de>
  -
  - @author René Gieling <github@dartcafe.de>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<div class="user_settings">
		<CheckboxRadioSwitch :checked.sync="createPollLimited" type="switch">
			{{ t('polls', 'Disallow poll creation for all users') }}
		</CheckboxRadioSwitch>
		<div v-if="createPollLimited" class="settings_details">
			<div>{{ t('polls','Allow poll creation for the following groups') }}</div>
			<Multiselect
				v-model="createPollGroups"
				class="stretch"
				label="displayName"
				track-by="id"
				:options="groups"
				:user-select="true"
				:clear-on-select="false"
				:preserve-search="true"
				:multiple="true"
				:loading="isLoading"
				:placeholder="t('polls', 'Leave empty to disallow for all.')"
				@search-change="loadGroups" />
		</div>
	</div>
</template>

<script>

import debounce from 'lodash/debounce'
import { mapState } from 'vuex'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { CheckboxRadioSwitch, Multiselect } from '@nextcloud/vue'

export default {
	name: 'AdminPollCreation',

	components: {
		CheckboxRadioSwitch,
		Multiselect,
	},

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

		// Add bindings
		createPollLimited: {
			get() {
				return !this.appSettings.allowPollCreation
			},
			set(value) {
				this.writeValue({ allowPollCreation: !value })
			},
		},
		createPollGroups: {
			get() {
				return this.appSettings.pollCreationGroups
			},
			set(value) {
				this.writeValue({ pollCreationGroups: value })
			},
		},
	},

	created() {
		this.loadGroups('')
	},

	methods: {
		async writeValue(value) {
			await this.$store.commit('appSettings/set', value)
			this.$store.dispatch('appSettings/write')
		},
		loadGroups: debounce(async function(query) {
			let endPoint = generateUrl(`apps/polls/groups/${query}`)

			if (!query.trim()) {
				endPoint = generateUrl('apps/polls/groups')
			}
			this.isLoading = true

			if (this.searchToken) {
				this.searchToken.cancel()
			}
			this.searchToken = axios.CancelToken.source()
			try {
				const response = await axios.get(endPoint, { cancelToken: this.searchToken.token })
				this.groups = response.data.groups
				this.isLoading = false
			} catch (e) {
				if (axios.isCancel(e)) {
					// request was cancelled
				} else {
					console.error(e.response)
					this.isLoading = false
				}
			}
		}, 250),
	},
}
</script>

<style lang="scss">
	.user_settings {
		padding-top: 16px;
	}

	.settings_details {
		padding-bottom: 16px;
		margin-left: 36px;
		input, .stretch {
			width: 100%;
		}
	}
</style>
