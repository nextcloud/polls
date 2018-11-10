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
	<div>
		<h2> {{ t('polls', 'Share with') }}</h2>

		<multiselect
			v-model="shares"
			:options="users"
			label="displayName"
			track-by="user"
			:multiple="true"
			:user-select="true"
			:tag-width="80"
			:clear-on-select="false"
			:preserve-search="true"
			:options-limit="20"
			id="ajax"
			@search-change="loadUsersAsync"
			@close="updateShares"
			:loading="isLoading"
			:internal-search="false"
			:searchable="true"
			:preselect-first="true"
			:placeholder="placeholder">
			<template slot="selection" slot-scope="{ values, search, isOpen }">
							<span class="multiselect__single" v-if="values.length &amp;&amp; !isOpen">
								{{ values.length }} users selected
							</span>
			</template>
		</multiselect>

		<transition-group tag="ul" v-bind:css="false" class="shared-list">
			<li v-for="(item, index) in sortedShares"
				v-bind:key="item.displayName"
				v-bind:data-index="index">
				<user-div :user-id="item.user" :display-name="item.displayName" :type="item.type" :hide-names="hideNames"></user-div>
				<div class="options">
					<a @click="removeShare(index, item)" class="icon icon-delete svg delete-poll"></a>
				</div>
			</li>
		</transition-group>
	</div>
</template>

<script>
	import axios from 'axios'
	import { Multiselect } from 'nextcloud-vue'

	export default {
		components: {
			Multiselect
		},

		props: ['placeholder', 'activeShares','hideNames'],

		data: function () {
			return {
				shares: [],
				users: [],
				isLoading: false,
				siteUsersListOptions: {
					getUsers: true,
					getGroups: true,
					query: ''
				}
			}
		},

		computed: {
			sortedShares: function() {
				return this.shares.sort(this.sortByDisplayname)
			}
		},

		methods: {
			removeShare: function (index, item){
				this.$emit('remove-share', item)
			},

			updateShares: function (){
				this.$emit('update-shares', this.shares)
			},

			loadUsersAsync: function (query) {
				this.isLoading = false
				this.siteUsersListOptions.query = query
				axios.post(OC.generateUrl('apps/polls/get/siteusers'), this.siteUsersListOptions)
				.then((response) => {
					this.users = response.data.siteusers
					this.isLoading = false
				}, (error) => {
					console.log(error.response)
				})
			},

			sortByDisplayname: function (a, b) {
					if (a.displayName.toLowerCase() < b.displayName.toLowerCase()) return -1
					if (a.displayName.toLowerCase() > b.displayName.toLowerCase()) return 1
					return 0
			}

		},
		watch: {
			activeShares(value) {
			this.shares = value.slice(0)
		}
}
	}
</script>

<style lang="scss">

	.shared-list {
		display: flex;
		flex-wrap: wrap;
		justify-content: flex-start;
		padding-top: 8px;

		> li {
			display: flex;
		}
	}

	.options {
		display: flex;
		position: relative;
		top: -12px;
		left: -13px;
	}

	.multiselect.multiselect-vue {
		width: 100%;
	}
</style>
