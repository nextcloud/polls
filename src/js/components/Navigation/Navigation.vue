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

<template lang="html">
	<AppNavigation>
		<AppNavigationNew :text="t('polls', 'Add new Poll')" @click="toggleCreateDlg" />
		<CreateDlg v-show="createDlg" @closeCreate="closeCreate()" />
		<ul>
			<AppNavigationItem
				:title="t('polls', 'All polls')"
				:allow-collapse="true"
				icon="icon-folder"
				:to="{ name: 'list', params: {type: 'all'}}"
				:open="true">
				<ul>
					<AppNavigationItem
						v-for="(poll) in allPolls"
						:key="poll.id"
						:title="poll.title"
						:icon="pollIcon(poll.type)"
						:to="{name: 'vote', params: {id: poll.id}}" />
				</ul>
			</AppNavigationItem>
			<AppNavigationItem
				:title="t('polls', 'My polls')"
				:allow-collapse="true"
				icon="icon-user"
				:to="{ name: 'list', params: {type: 'my'}}"
				:open="false">
				<ul>
					<AppNavigationItem
						v-for="(poll) in myPolls"
						:key="poll.id"
						:title="poll.title"
						:icon="pollIcon(poll.type)"
						:to="{name: 'vote', params: {id: poll.id}}" />
				</ul>
			</AppNavigationItem>
			<AppNavigationItem
				:title="t('polls', 'Public polls')"
				:allow-collapse="true"
				icon="icon-link"
				:to="{ name: 'list', params: {type: 'public'}}"
				:open="false">
				<ul>
					<AppNavigationItem
						v-for="(poll) in publicPolls"
						:key="poll.id"
						:title="poll.title"
						:icon="pollIcon(poll.type)"
						:to="{name: 'vote', params: {id: poll.id}}" />
				</ul>
			</AppNavigationItem>
			<AppNavigationItem
				:title="t('polls', 'Hidden polls')"
				:allow-collapse="true"
				icon="icon-password"
				:to="{ name: 'list', params: {type: 'hidden'}}"
				:open="false">
				<ul>
					<AppNavigationItem
						v-for="(poll) in hiddenPolls"
						:key="poll.id"
						:title="poll.title"
						:icon="pollIcon(poll.type)"
						:to="{name: 'vote', params: {id: poll.id}}" />
				</ul>
			</AppNavigationItem>
			<AppNavigationItem
				:title="t('polls', 'Deleted polls')"
				:allow-collapse="true"
				icon="icon-delete"
				:to="{ name: 'list', params: {type: 'deleted'}}"
				:open="false">
				<ul>
					<AppNavigationItem
						v-for="(poll) in deletedPolls"
						:key="poll.id"
						:title="poll.title"
						:icon="pollIcon(poll.type)"
						:to="{name: 'vote', params: {id: poll.id}}" />
				</ul>
			</AppNavigationItem>
		</ul>
	</AppNavigation>
</template>

<script>

import { AppNavigation, AppNavigationNew, AppNavigationItem } from '@nextcloud/vue'
import { mapGetters } from 'vuex'
import CreateDlg from '../Create/CreateDlg'

export default {
	name: 'Navigation',
	components: {
		AppNavigation,
		AppNavigationNew,
		AppNavigationItem,
		CreateDlg
	},

	data() {
		return {
			createDlg: false
		}
	},

	computed: {
		...mapGetters([
			'allPolls',
			'myPolls',
			'publicPolls',
			'hiddenPolls',
			'deletedPolls'
		]),

		pollList() {
			return this.$store.state.polls.list
		}
	},

	mounted() {
		this.$root.$on('updatePolls', function() {
			this.loading = true
			this.$store
				.dispatch('loadPolls')
				.then(() => {
					this.loading = false
				})
				.catch(error => {
					this.loading = false
					console.error('refresh poll: ', error.response)
					OC.Notification.showTemporary(t('polls', 'Error loading polls'), { type: 'error' })
				})
		})
	},

	created() {
		this.refreshPolls()
	},

	methods: {
		closeCreate() {
			this.createDlg = false
		},

		toggleCreateDlg() {
			this.createDlg = !this.createDlg
		},

		pollIcon(type) {
			if (type === '0') {
				return 'icon-calendar'
			} else {
				return 'icon-toggle-filelist'
			}
		},

		refreshPolls() {
			if (this.$route.name !== 'publicVote') {

				this.loading = true
				this.$store
					.dispatch('loadPolls')
					.then(() => {
						this.loading = false
					})
					.catch(error => {
						this.loading = false
						console.error('refresh poll: ', error.response)
						OC.Notification.showTemporary(t('polls', 'Error loading polls'), { type: 'error' })
					})
			}
		}
	}
}
</script>

<style lang="scss">
	.config-box {
		display: flex;
		flex-direction: column;
		padding: 8px;
		& > * {
			padding-left: 21px;
		}

		& > input {
			margin-left: 24px;
			width: auto;

		}

		& > textarea {
			margin-left: 24px;
			width: auto;
			padding: 7px 6px;
		}

		& > .title {
			display: flex;
			background-position: 0 2px;
			padding-left: 24px;
			opacity: 0.7;
			font-weight: bold;
			margin-bottom: 4px;
			& > span {
				padding-left: 4px;
			}
		}
	}
</style>
