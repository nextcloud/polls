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
	<AppContent>
		<div class="main-container">
			<div v-if="noPolls" class="">
				<div class="icon-polls" />
				<h2> {{ t('No existing polls.') }} </h2>
			</div>

			<transition-group v-if="!noPolls" name="list" tag="div"
				class="table">
				<PollListItem key="0" :header="true"
					:sort="sort" :reverse="reverse" @sortList="setSort($event)" />
				<li is="PollListItem"
					v-for="(poll, index) in sortedList"
					:key="poll.id"
					:poll="poll"
					@deletePoll="removePoll(index, poll)"
					@editPoll="callPoll(index, poll, 'edit')"
					@clonePoll="callPoll(index, poll, 'clone')" />
			</transition-group>
		</div>
		<LoadingOverlay v-if="isLoading" />
	</AppContent>
</template>

<script>
import { AppContent } from '@nextcloud/vue'
import PollListItem from '../components/PollList/PollListItem'
import { mapGetters } from 'vuex'
import sortBy from 'lodash/sortBy'
import LoadingOverlay from '../components/Base/LoadingOverlay'

export default {
	name: 'PollList',

	components: {
		AppContent,
		LoadingOverlay,
		PollListItem
	},

	data() {
		return {
			noPolls: false,
			isLoading: false,
			sort: 'created',
			reverse: true
		}
	},

	computed: {
		...mapGetters([
			'allPolls',
			'myPolls',
			'publicPolls',
			'hiddenPolls',
			'participatedPolls',
			'deletedPolls'
		]),

		filteredList() {
			if (this.$route.params.type === 'my') {
				return this.myPolls
			} else if (this.$route.params.type === 'public') {
				return this.publicPolls
			} else if (this.$route.params.type === 'hidden') {
				return this.hiddenPolls
			} else if (this.$route.params.type === 'deleted') {
				return this.deletedPolls
			} else if (this.$route.params.type === 'participated') {
				return this.participatedPolls
			} else {
				return this.allPolls
			}
		},

		sortedList() {
			if (this.reverse) {
				return sortBy(this.filteredList, this.sort).reverse()
			} else {
				return sortBy(this.filteredList, this.sort)
			}
		}

	},

	methods: {
		setSort(payload) {
			if (this.sort === payload.sort) {
				this.reverse = !this.reverse
			} else {
				this.sort = payload.sort
				this.reverse = true
			}
		},

		callPoll(index, poll, name) {
			this.$router.push({
				name: name,
				params: {
					id: poll.id
				}
			})
		}
	}
}
</script>

<style lang="scss" scoped>
	#app-content {
		// flex-direction: column;
	}

	.main-container {
		flex: 1;
	}

	.table {
		width: 100%;
		// margin-top: 45px;
		display: flex;
		flex-direction: column;
		flex: 1;
		flex-wrap: nowrap;
	}

	#emptycontent {
		.icon-polls {
			background-color: black;
			-webkit-mask: url('./img/app.svg') no-repeat 50% 50%;
			mask: url('./img/app.svg') no-repeat 50% 50%;
		}
	}
</style>
