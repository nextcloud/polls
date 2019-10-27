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
		<create-dlg v-show="createDlg" @closeCreate="closeCreate()"/>
		<app-content-details
			v-for="(poll) in pollList"
			:key="poll.id">
			<router-link
				:to="{name: 'vote', params: {id: poll.id}}"
				class="thumbnail"
				:class="eventIcon(poll.event.type)">
				<div class="name">
					{{ poll.event.title }}
					<span v-if="poll.event.expired" class="label error">{{ t('poll', 'Expired') }}</span>
				</div>
				<div class="description">
					{{ poll.event.description }}
				</div>
			</router-link>
		</app-content-details>
	</app-navigation>
</template>

<script>

import { AppNavigation, AppNavigationNew, AppContentDetails } from 'nextcloud-vue'
import createDlg from '../create/createDlg'

export default {
	name: 'Navigation',
	components: {
		AppNavigation,
		AppNavigationNew,
		AppContentDetails,
		createDlg
	},

	data() {
		return {
			createDlg: false
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
		closeCreate() {
			this.createDlg = false
		},

		toggleCreateDlg() {
			this.createDlg = !this.createDlg
		},

		eventIcon(type) {
			if (type === '0') {
				return 'datePoll'
			} else {
				return 'textPoll'
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

<style lang="scss" scoped>

.name,
.description {
	overflow: hidden;
	white-space: nowrap;
	text-overflow: ellipsis;
}
.description {
	opacity: 0.5;
}

.app-content-details {
	padding: 4px 0 4px 0;
}

a.active, a:hover {
	box-shadow: var(--color-primary-element) 4px 0px inset;
	opacity: 1;
}

.thumbnail {
	background-size: 16px 16px;
	background-position: 14px center;
	background-repeat: no-repeat;
	display: block;
	justify-content: space-between;
	// line-height: 44px;
	min-height: 44px;
	padding: 0 12px 0 44px;
	overflow: hidden;
	box-sizing: border-box;
	white-space: nowrap;
	text-overflow: ellipsis;
	color: var(--color-main-text);
	opacity: 0.57;
	// flex: 1 1 0px;
	// z-index: 100;

	&.datePoll {
		background-image: var(--icon-calendar-000);
		// mask-image: var(--icon-calendar-000) no-repeat 50% 50%;
		// -webkit-mask: var(--icon-calendar-000) no-repeat 50% 50%;
		// mask-size: 16px;
	}
	&.textPoll {
		background-image: var(--icon-organization-000);
		// mask-image: var(--icon-organization-000) no-repeat 50% 50%;
		// -webkit-mask: var(--icon-organization-000) no-repeat 50% 50%;
		// mask-size: 16px;
	}
	&.expired {
		background-color: var(--color-background-darker);
	}
}

</style>
