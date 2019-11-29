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
				:to="{ name: 'list'}"
				:open="true">
				<ul>
					<AppNavigationItem
						v-for="(poll) in pollList"
						:key="poll.id"
						:title="poll.title"
						:icon="eventIcon(poll.type)"
						:to="{name: 'vote', params: {id: poll.id}}" />
				</ul>
			</AppNavigationItem>
			<AppNavigationItem
				:title="t('polls', 'My polls')"
				:allow-collapse="true"
				icon="icon-folder"
				:open="false">
				<ul>
					<AppNavigationItem
						v-for="(poll) in myPolls"
						:key="poll.id"
						:title="poll.title"
						:icon="eventIcon(poll.type)"
						:to="{name: 'vote', params: {id: poll.id}}" />
				</ul>
			</AppNavigationItem>
			<AppNavigationItem
				:title="t('polls', 'Public polls')"
				:allow-collapse="true"
				icon="icon-folder"
				:open="false">
				<ul>
					<AppNavigationItem
						v-for="(poll) in publicPolls"
						:key="poll.id"
						:title="poll.title"
						:icon="eventIcon(poll.type)"
						:to="{name: 'vote', params: {id: poll.id}}" />
				</ul>
			</AppNavigationItem>
			<AppNavigationItem
				:title="t('polls', 'Hidden polls')"
				:allow-collapse="true"
				icon="icon-folder"
				:open="false">
				<ul>
					<AppNavigationItem
						v-for="(poll) in hiddenPolls"
						:key="poll.id"
						:title="poll.title"
						:icon="eventIcon(poll.type)"
						:to="{name: 'vote', params: {id: poll.id}}" />
				</ul>
			</AppNavigationItem>
		</ul>

		<AppNavigationSettings>
			<router-link :to="{ name: 'list'}">
				List
			</router-link>
		</AppNavigationSettings>
	</AppNavigation>
</template>

<script>

import { AppNavigation, AppNavigationNew, AppNavigationItem, AppNavigationSettings } from '@nextcloud/vue'
import { mapGetters } from 'vuex'
import CreateDlg from '../create/createDlg'
import state from './store/polls.js'

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
		this.$store.registerModule('polls', state)
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
					OC.Notification.showTemporary(t('polls', 'Error loading polls'), { type: 'error' })
				})

		}
	}
}
</script>
