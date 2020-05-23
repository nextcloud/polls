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
		<AppNavigationNew button-class="icon-add" :text="t('polls', 'Add new Poll')" @click="toggleCreateDlg" />
		<CreateDlg v-show="createDlg" ref="createDlg" @closeCreate="closeCreate()" />
		<ul>
			<AppNavigationItem v-for="(pollCategory) in pollCategories" :key="pollCategory.id"
				:title="pollCategory.title" :allow-collapse="true" :pinned="pollCategory.pinned"
				:icon="pollCategory.icon" :to="{ name: 'list', params: {type: pollCategory.id}}" :open="false">
				<ul>
					<PollNavigationItems v-for="(poll) in filteredPolls(pollCategory.id)"
						:key="poll.id"
						:poll="poll"
						@switchDeleted="switchDeleted(poll.id)"
						@clonePoll="clonePoll(poll.id)"
						@deletePermanently="deletePermanently(poll.id)" />
				</ul>
			</AppNavigationItem>
		</ul>
		<AppNavigationSettings :title="t('core', 'Settings')">
			<NavigationSettings />
		</AppNavigationSettings>
	</AppNavigation>
</template>

<script>

import { AppNavigation, AppNavigationNew, AppNavigationItem, AppNavigationSettings } from '@nextcloud/vue'
import { mapGetters } from 'vuex'
import CreateDlg from '../Create/CreateDlg'
import PollNavigationItems from './PollNavigationItems'
import NavigationSettings from './NavigationSettings'
import { emit } from '@nextcloud/event-bus'

export default {
	name: 'Navigation',
	components: {
		AppNavigation,
		AppNavigationNew,
		AppNavigationItem,
		AppNavigationSettings,
		NavigationSettings,
		CreateDlg,
		PollNavigationItems,
	},

	data() {
		return {
			createDlg: false,
			reloadInterval: 30000,
			pollCategories: [
				{
					id: 'relevant',
					title: t('polls', 'Relevant'),
					icon: 'icon-details',
					pinned: false,
				},
				{
					id: 'my',
					title: t('polls', 'My polls'),
					icon: 'icon-user',
					pinned: false,
				},
				{
					id: 'public',
					title: t('polls', 'Public polls'),
					icon: 'icon-link',
					pinned: false,
				},
				{
					id: 'all',
					title: t('polls', 'All polls'),
					icon: 'icon-polls',
					pinned: false,
				},
				{
					id: 'expired',
					title: t('polls', 'Expired polls'),
					icon: 'icon-polls-expired',
					pinned: false,
				},
				{
					id: 'deleted',
					title: t('polls', 'Deleted polls'),
					icon: 'icon-delete',
					pinned: true,
				},
			],

		}
	},

	computed: {
		...mapGetters(['filteredPolls']),

		pollList() {
			return this.$store.state.polls.polls
		},
	},

	created() {
		this.timedReload()
	},

	beforeDestroy() {
		window.clearInterval(this.reloadTimer)
	},

	methods: {
		closeCreate() {
			this.createDlg = false
		},

		timedReload() {
			// reload poll list periodically
			this.reloadTimer = window.setInterval(() => {
				emit('update-polls')
			}, this.reloadInterval)
		},

		toggleCreateDlg() {
			this.createDlg = !this.createDlg
			if (this.createDlg) {
				this.$refs.createDlg.setFocus()
			}
		},

		clonePoll(pollId) {
			this.$store
				.dispatch('clonePoll', { pollId: pollId })
				.then((response) => {
					emit('update-polls')
					this.$router.push({ name: 'vote', params: { id: response.pollId } })
				})
		},

		switchDeleted(pollId) {
			this.$store
				.dispatch('switchDeleted', { pollId: pollId })
				.then((response) => {
					emit('update-polls')
				})

		},

		deletePermanently(pollId) {
			this.$store
				.dispatch('deletePermanently', { pollId: pollId })
				.then((response) => {
					// if we permanently delete current selected poll,
					// reload deleted polls route
					if (this.$route.params.id && this.$route.params.id === pollId) {
						this.$router.push({ name: 'list', params: { type: 'deleted' } })
					}
					emit('update-polls')
				})

		},
	},
}
</script>

<style lang="scss">
	.expired {
		.app-navigation-entry-icon, .app-navigation-entry__title {
			opacity: 0.6;
		}
	}

</style>
