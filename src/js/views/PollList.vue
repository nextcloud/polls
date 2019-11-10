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
	<app-content>
		<div class="main-container">

			<div v-if="noPolls" class="">
				<div class="icon-polls" />
				<h2> {{ t('No existing polls.') }} </h2>
				<router-link :to="{ name: 'create'}" class="button new">
					<span>{{ t('polls', 'Click here to add a poll') }}</span>
				</router-link>
			</div>

			<transition-group v-if="!noPolls" name="list" tag="div"
				class="table">
				<PollListItem key="0" :header="true" />
				<li is="PollListItem"
					v-for="(poll, index) in pollList"
					:key="poll.id"
					:poll="poll"
					@deletePoll="removePoll(index, poll.event)"
					@editPoll="callPoll(index, poll.event, 'edit')"
					@clonePoll="callPoll(index, poll.event, 'clone')" />
			</transition-group>
		</div>
		<loading-overlay v-if="loading" />
		<!-- <modal-dialog /> -->
	</app-content>
</template>

<script>
import PollListItem from '../components/PollList/PollListItem'

export default {
	name: 'PollList',

	components: {
		PollListItem
	},

	data() {
		return {
			noPolls: false,
			loading: true
		}
	},

	computed: {
		pollList() {
			return this.$store.state.polls.list
		}
	},

	created() {
		this.refreshPolls()
	},

	methods: {
		callPoll(index, event, name) {
			this.$router.push({
				name: name,
				params: {
					id: event.id
				}
			})
		},

		refreshPolls() {
			this.loading = true
			this.$store
				.dispatch('loadPolls')
				.then(response => {
					this.loading = false
				})
				.catch(error => {
					this.loading = false
					console.error('refresh poll: ', error.response)
					OC.Notification.showTemporary(t('polls', 'Error loading polls"', 1, event.title), { type: 'error' })
				})
		},

		// removePoll(index, event) {
		// 	const params = {
		// 		title: t('polls', 'Delete poll'),
		// 		text: t('polls', 'Do you want to delete "%n"?', 1, event.title),
		// 		buttonHideText: t('polls', 'No, keep poll.'),
		// 		buttonConfirmText: t('polls', 'Yes, delete poll.'),
		// 		// Call store action here
		// 		onConfirm: () => {
		// 			this.loading = true
		// 			this.$store
		// 				.dispatch({
		// 					type: 'deletePollPromise',
		// 					event: event
		// 				})
		// 				.then(response => {
		// 					this.loading = false
		// 					this.refreshPolls()
		// 					OC.Notification.showTemporary(t('polls', 'Poll "%n" deleted', 1, event.title), { type: 'success' })
		// 				})
		// 				.catch(error => {
		// 					this.loading = false
		// 					console.error('remove poll: ', error.response)
		// 					OC.Notification.showTemporary(t('polls', 'Error while deleting Poll "%n"', 1, event.title), { type: 'error' })
		// 				})
		// 		}
		// 	}
		//
		// }
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
