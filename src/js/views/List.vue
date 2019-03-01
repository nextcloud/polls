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
	<div id="app-content">
		<controls>
			<router-link :to="{ name: 'create'}" class="button">
				<span class="symbol icon-add" />
				<span class="hidden-visually">
					{{ t('polls', 'New') }}
				</span>
			</router-link>
		</controls>

		<div v-if="noPolls" class="">
			<div class="icon-polls" />
			<h2> {{ t('No existing polls.') }} </h2>
			<router-link :to="{ name: 'create'}" class="button new">
				<span>{{ t('polls', 'Click here to add a poll') }}</span>
			</router-link>
		</div>

		<transition-group
			v-if="!noPolls"
			name="list"
			tag="div"
			class="table"
		>
			<poll-list-item
				key="0"
				:header="true"
			/>
			<li
				is="poll-list-item"
				v-for="(poll, index) in polls"
				:key="poll.id"
				:poll="poll"
				@deletePoll="removePoll(index, poll.event)"
				@editPoll="editPoll(index, poll.event, 'edit')"
				@clonePoll="editPoll(index, poll.event, 'clone')"
			/>
		</transition-group>
		<loading-overlay v-if="loading" />
		<modal-dialog />
	</div>
</template>

<script>
// import moment from 'moment'
// import lodash from 'lodash'
import pollListItem from '../components/pollListItem'

export default {
	name: 'List',

	components: {
		pollListItem
	},

	data() {
		return {
			noPolls: false,
			loading: true,
			polls: []
		}
	},

	created() {
		this.indexPage = OC.generateUrl('apps/polls/')
		this.loadPolls()
	},

	methods: {
		loadPolls() {
			this.loading = true
			this.$http.get(OC.generateUrl('apps/polls/get/polls'))
				.then((response) => {
					this.polls = response.data
					this.loading = false
				}, (error) => {
					/* eslint-disable-next-line no-console */
					console.log(error.response)
					this.loading = false
				})
		},

		editPoll(index, event, name) {
			this.$router.push({
				name: name,
				params: {
					hash: event.id
				}
			})
		},

		clonePoll(index, event, name) {
			this.$router.push({
				name: name,
				params: {
					hash: event.id
				}
			})
		},

		removePoll(index, event) {
			const params = {
				title: t('polls', 'Delete poll'),
				text: t('polls', 'Do you want to delete "%n"?', 1, event.title),
				buttonHideText: t('polls', 'No, keep poll.'),
				buttonConfirmText: t('polls', 'Yes, delete poll.'),
				onConfirm: () => {
					// this.deletePoll(index, event)
					this.$http.post(OC.generateUrl('apps/polls/remove/poll'), event)
						.then((response) => {
							this.polls.splice(index, 1)
							OC.Notification.showTemporary(t('polls', 'Poll "%n" deleted', 1, event.title))
						}, (error) => {
							OC.Notification.showTemporary(t('polls', 'Error while deleting Poll "%n"', 1, event.title))
							/* eslint-disable-next-line no-console */
							console.log(error.response)
						}
						)
				}
			}
			this.$modal.show(params)
		}

	}
}
</script>

<style lang="scss">

.table {
	width: 100%;
	margin-top: 45px;
	display: flex;
	flex-direction: column;
	flex-grow: 1;
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
