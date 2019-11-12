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
	<app-navigation>
		<app-navigation-new :text="t('polls', 'Add new Poll')" @click="toggleCreateDlg" />
		<create-dlg v-show="createDlg" @closeCreate="closeCreate()" />
		<ul>
			<app-navigation-item
				:title="t('polls', 'All polls')"
				:allow-collapse="true"
				icon="icon-folder"
				:to="{ name: 'list'}"
				:open="true">
				<ul>
					<app-navigation-item
						v-for="(poll) in pollList"
						:key="poll.id"
						:title="poll.event.title"
						:icon="eventIcon(poll.event.type)"
						:to="{name: 'vote', params: {id: poll.id}}" />
				</ul>
			</app-navigation-item>
			<app-navigation-item
				:title="t('polls', 'My polls')"
				:allow-collapse="true"
				icon="icon-folder"
				:open="false">
				<ul>
					<app-navigation-item
						v-for="(poll) in myPolls"
						:key="poll.id"
						:title="poll.event.title"
						:icon="eventIcon(poll.event.type)"
						:to="{name: 'vote', params: {id: poll.id}}" />
				</ul>
			</app-navigation-item>
			<app-navigation-item
				:title="t('polls', 'Public polls')"
				:allow-collapse="true"
				icon="icon-folder"
				:open="false">
				<ul>
					<app-navigation-item
						v-for="(poll) in publicPolls"
						:key="poll.id"
						:title="poll.event.title"
						:icon="eventIcon(poll.event.type)"
						:to="{name: 'vote', params: {id: poll.id}}" />
				</ul>
			</app-navigation-item>
			<app-navigation-item
				:title="t('polls', 'Hidden polls')"
				:allow-collapse="true"
				icon="icon-folder"
				:open="false">
				<ul>
					<app-navigation-item
						v-for="(poll) in hiddenPolls"
						:key="poll.id"
						:title="poll.event.title"
						:icon="eventIcon(poll.event.type)"
						:to="{name: 'vote', params: {id: poll.id}}" />
				</ul>
			</app-navigation-item>
		</ul>

		<app-navigation-settings>
			<router-link :to="{ name: 'list'}">
				List
			</router-link>
		</app-navigation-settings>
	</app-navigation>
</template>

<script>

import { AppNavigation, AppNavigationNew, AppNavigationItem, AppNavigationSettings } from '@nextcloud/vue'
import { mapGetters } from 'vuex'
import CreateDlg from '../create/createDlg'

export default {
	name: 'Navigation',
	components: {
		AppNavigation,
		AppNavigationNew,
		AppNavigationItem,
		AppNavigationSettings,
		CreateDlg
	},

	data() {
		return {
			createDlg: false
		}
	},

	computed: {

		...mapGetters([
			'myPolls',
			'publicPolls',
			'hiddenPolls'
		]),

		pollList() {
			return this.$store.state.polls.list
		}
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

		eventIcon(type) {
			if (type === '0') {
				return 'icon-calendar'
			} else {
				return 'icon-toggle-filelist'
			}
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

		}
	}
}
</script>

// <style lang="scss" scoped>
//
// .name,
// .description {
// 	overflow: hidden;
// 	white-space: nowrap;
// 	text-overflow: ellipsis;
// }
// .description {
// 	opacity: 0.5;
// }
//
// .app-content-details {
// 	padding: 4px 0 4px 0;
// }
//
// a.active, a:hover {
// 	box-shadow: var(--color-primary-element) 4px 0px inset;
// 	opacity: 1;
// }
//
// .thumbnail {
// 	background-size: 16px 16px;
// 	background-position: 14px center;
// 	background-repeat: no-repeat;
// 	display: block;
// 	justify-content: space-between;
// 	// line-height: 44px;
// 	min-height: 44px;
// 	padding: 0 12px 0 44px;
// 	overflow: hidden;
// 	box-sizing: border-box;
// 	white-space: nowrap;
// 	text-overflow: ellipsis;
// 	color: var(--color-main-text);
// 	opacity: 0.57;
// 	// flex: 1 1 0px;
// 	// z-index: 100;
//
// 	&.datePoll {
// 		background-image: var(--icon-calendar-000);
// 		// mask-image: var(--icon-calendar-000) no-repeat 50% 50%;
// 		// -webkit-mask: var(--icon-calendar-000) no-repeat 50% 50%;
// 		// mask-size: 16px;
// 	}
// 	&.textPoll {
// 		background-image: var(--icon-organization-000);
// 		// mask-image: var(--icon-organization-000) no-repeat 50% 50%;
// 		// -webkit-mask: var(--icon-organization-000) no-repeat 50% 50%;
// 		// mask-size: 16px;
// 	}
// 	&.expired {
// 		background-color: var(--color-background-darker);
// 	}
// }
//
// </style>
