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
	<Content app-name="polls" :style="appStyle" :class="[transitionClass, { 'experimental': settings.experimental, 'bgimage': settings.useImage, 'bgcolored': settings.experimental }]">
		<SettingsDlg />
		<Navigation v-if="getCurrentUser()" :class="{ 'glassy': settings.glassyNavigation }" />
		<router-view />
		<SideBar v-if="sideBarOpen && $store.state.poll.id"
			:active="activeTab"
			:class="{ 'glassy': settings.glassySidebar }" />
	</Content>
</template>

<script>
import Navigation from './components/Navigation/Navigation'
import SideBar from './components/SideBar/SideBar'
import SettingsDlg from './components/Settings/SettingsDlg'
import { getCurrentUser } from '@nextcloud/auth'
import { showError } from '@nextcloud/dialogs'
import { Content } from '@nextcloud/vue'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import { mapState } from 'vuex'
import '@nextcloud/dialogs/styles/toast.scss'
import './assets/scss/hacks.scss'
import './assets/scss/icons.scss'
import './assets/scss/colors.scss'
import './assets/scss/transitions.scss'
import './assets/scss/experimental.scss'

export default {
	name: 'App',
	components: {
		Navigation,
		Content,
		SettingsDlg,
		SideBar,
	},

	data() {
		return {
			sideBarOpen: (window.innerWidth > 920),
			activeTab: 'comments',
			transitionClass: 'transitions-active',
		}
	},

	computed: {
		...mapState({
			settings: state => state.settings.user,
		}),
		appStyle() {
			if (this.settings.useImage && this.settings.experimental) {
				return {
					backgroundImage: 'url(' + this.settings.imageUrl + ')',
					backgroundSize: 'cover',
					backgroundPosition: 'center center',
					backgroundAttachment: 'fixed',
					backgroundRepeat: 'no-repeat',
				}
			} else {
				return {}
			}
		},
	},

	watch: {
		$route(from, to) {
			console.log('route changed', 'from ' + from.params.id + ' to ' + to.params.id)
			if (from.params.id !== to.params.id) {
				console.log('poll id', this.$route.params.id)
				this.loadPoll()
			}
		},
	},

	created() {
		subscribe('transitions-off', (delay) => {
			this.transitionClass = ''
			if (delay) {
				setTimeout(() => {
					this.transitionClass = 'transitions-active'
				}, delay)
			}
		})

		subscribe('transitions-on', () => {
			this.transitionClass = 'transitions-active'
		})

		subscribe('toggle-sidebar', (payload) => {
			if (payload === undefined) {
				this.sideBarOpen = !this.sideBarOpen
			} else {
				if (payload.activeTab !== undefined) {
					this.activeTab = payload.activeTab
				}
				if (payload.open !== undefined) {
					this.sideBarOpen = payload.open
				} else {
					this.sideBarOpen = !this.sideBarOpen
				}
			}

		})

		if (getCurrentUser()) {
			this.$store.dispatch('settings/get')
			this.updatePolls()
			subscribe('update-polls', () => {
				this.updatePolls()
			})
		}
	},

	beforeDestroy() {
		unsubscribe('update-polls')
		unsubscribe('toggle-sidebar')
	},

	methods: {
		loadPoll() {
			this.$store.dispatch('poll/comments/list')
			this.$store.dispatch('poll/options/list')
			this.$store.dispatch('poll/votes/list')
		},

		updatePolls() {
			if (getCurrentUser()) {

				this.$store.dispatch('polls/load')
					.catch(() => {
						showError(t('polls', 'Error loading poll list'))
					})
			}
			if (getCurrentUser().isAdmin) {

				this.$store.dispatch('pollsAdmin/load')
					.catch(() => {
						showError(t('polls', 'Error loading poll list'))
					})
			}
		},
	},
}

</script>

<style  lang="scss">

.description {
	// white-space: pre-wrap;
	margin: 8px 0;
}

.linkified {
	font-weight: bold;
	text-decoration: underline;
}

input {
	background-repeat: no-repeat;
	background-position: 98%;

	&.error {
		border-color: var(--color-error);
		background-color: var(--color-background-error);
		background-image: var(--icon-polls-no);
		color: var(--color-text-maxcontrast);
	}

	&.checking {
		border-color: var(--color-warning);
		background-image: var(--icon-polls-loading);
	}

	&.success, &.icon-confirm.success {
		border-color: var(--color-success);
		background-image: var(--icon-polls-yes);
		background-color: var(--color-background-success) !important;
		color: var(--color-text-maxcontrast);
	}

	&.icon {
		flex: 0;
		padding: 0 17px;
	}
}

.label {
	border: solid 1px;
	border-radius: var(--border-radius);
	padding: 1px 4px;
	margin: 0 4px;
	font-size: 60%;
	text-align: center;

	&.error {
		border-color: var(--color-error);
		background-color: var(--color-error);
		color: var(--color-primary-text);
	}

	&.success {
		border-color: var(--color-success);
		background-color: var(--color-success);
		color: var(--color-primary-text);
	}
}

.modal__content {
	padding: 14px;
	display: flex;
	flex-direction: column;
	color: var(--color-main-text);
	input {
		width: 100%;
	}
}

.modal__buttons__spacer {
	flex: 1;
}

.modal__buttons {
	display: flex;
	justify-content: flex-end;
	align-items: center;
	.button {
		margin-left: 10px;
		margin-right: 0;
	}
}

.modal__buttons__link {
	text-decoration: underline;
}

.app-content {
	display: flex;
	flex-direction: column;
	padding: 0 8px;
	min-width: 320px;
}

</style>
