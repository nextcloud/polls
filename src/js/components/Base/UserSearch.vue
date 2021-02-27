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
	<Multiselect id="ajax"
		:options="users"
		:multiple="false"
		:user-select="true"
		:tag-width="80"
		:clear-on-select="false"
		:preserve-search="true"
		:options-limit="30"
		:loading="isLoading"
		:internal-search="false"
		:searchable="true"
		:preselect-first="true"
		:placeholder="placeholder"
		label="displayName"
		track-by="userId"
		@select="addShare"
		@search-change="loadUsersAsync">
		<template slot="selection" slot-scope="{ values, isOpen }">
			<span v-if="values.length &amp;&amp; !isOpen" class="multiselect__single">
				{{ values.length }} users selected
			</span>
		</template>
	</Multiselect>
</template>

<script>
import debounce from 'lodash/debounce'
import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
import { Multiselect } from '@nextcloud/vue'

export default {
	name: 'UserSearch',

	components: {
		Multiselect,
	},

	data() {
		return {
			searchToken: null,
			users: [],
			isLoading: false,
			placeholder: t('polls', 'Enter a name to start the search'),
		}
	},

	computed: {
	},

	methods: {
		loadUsersAsync: debounce(async function(query) {
			if (!query) {
				this.users = []
				return
			}
			this.isLoading = true
			if (this.searchToken) {
				this.searchToken.cancel()
			}
			this.searchToken = axios.CancelToken.source()
			try {
				const response = await axios.get(generateUrl('apps/polls/search/users/' + query), { cancelToken: this.searchToken.token })
				this.users = response.data.siteusers
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

		addShare(payload) {
			this.$store
				.dispatch('shares/add', {
					share: payload,
					type: payload.type,
					id: payload.id,
					emailAddress: payload.emailAddress,
				})
				.catch(error => {
					console.error('Error while adding share - Error: ', error)
					showError(t('polls', 'Error while adding share'))
				})
		},
	},
}
</script>

<style lang="scss">
	.multiselect {
		width: 100% !important;
		max-width: 100% !important;
	}
</style>
