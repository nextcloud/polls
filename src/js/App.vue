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
	<Content app-name="polls" :style="appStyle" :class="[transitionClass, { 'edit': acl.allowEdit, 'experimental': settings.experimental, 'bgimage': settings.useImage, 'bgcolored': settings.experimental }]">
		<SettingsDlg />
		<Navigation v-if="getCurrentUser()" :class="{ 'glassy': settings.glassyNavigation }" />
		<router-view />
		<SideBar v-if="sideBarOpen && $store.state.poll.id && (acl.allowEdit || poll.allowComment)"
			:active="activeTab"
			:class="{ 'glassy': settings.glassySidebar }" />
		<LoadingOverlay v-if="loading" />
	</Content>
</template>

<script>
import SettingsDlg from './components/Settings/SettingsDlg'
import { getCurrentUser } from '@nextcloud/auth'
import { showError } from '@nextcloud/dialogs'
import { Content } from '@nextcloud/vue'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import { mapState } from 'vuex'
import '@nextcloud/dialogs/styles/toast.scss'
import './assets/scss/colors.scss'
import './assets/scss/hacks.scss'
import './assets/scss/icons.scss'
import './assets/scss/print.scss'

// TODO: remove comments, when @media:prefers-color-scheme is completely supported by core
import './assets/scss/transitions.scss'
import './assets/scss/experimental.scss'
import { watchPolls } from './mixins/watchPolls'

export default {
	name: 'App',
	components: {
		Content,
		LoadingOverlay: () => import('./components/Base/LoadingOverlay'),
		Navigation: () => import('./components/Navigation/Navigation'),
		SettingsDlg,
		SideBar: () => import('./components/SideBar/SideBar'),
	},

	mixins: [watchPolls],

	data() {
		return {
			sideBarOpen: (window.innerWidth > 920),
			activeTab: 'comments',
			transitionClass: 'transitions-active',
			loading: false,
		}
	},

	computed: {
		...mapState({
			settings: (state) => state.settings.user,
			poll: (state) => state.poll,
			acl: (state) => state.poll.acl,
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
			}
			return {}
		},
	},

	watch: {
		$route(to, from) {
			this.watchPollsRestart()
			this.loadPoll()
		},
	},

	created() {
		if (getCurrentUser()) {
			this.$store.dispatch('settings/get')
			if (this.$route.name !== 'publicVote') {
				this.updatePolls()
			}
			if (this.$route.params.id && !this.$route.params.token) {
				this.loadPoll(true)
			}
		}

		this.watchPolls()

		subscribe('transitions-off', (delay) => {
			this.transitionsOff(delay)
		})

		subscribe('transitions-on', () => {
			this.transitionsOn()
		})

		subscribe('load-poll', (silent) => {
			this.loadPoll(silent)
		})

		subscribe('update-polls', () => {
			this.updatePolls()
		})

		subscribe('toggle-sidebar', (payload) => {
			if (payload === undefined) {
				this.sideBarOpen = !this.sideBarOpen
			} else {
				if (payload.activeTab !== undefined) {
					this.activeTab = payload.activeTab
				}
				if (payload.open === undefined) {
					this.sideBarOpen = !this.sideBarOpen
				} else {
					this.sideBarOpen = payload.open
				}
			}

		})
	},

	beforeDestroy() {
		this.cancelToken.cancel()
		unsubscribe('load-poll')
		unsubscribe('update-polls')
		unsubscribe('toggle-sidebar')
		unsubscribe('transitions-on')
		unsubscribe('transitions-off')
	},

	methods: {
		transitionsOn() {
			this.transitionClass = 'transitions-active'
		},

		transitionsOff(delay) {
			this.transitionClass = ''
			if (delay) {
				setTimeout(() => {
					this.transitionClass = 'transitions-active'
				}, delay)
			}
		},

		async loadPoll(silent) {
			const dispatches = []
			if (!silent) {
				this.loading = true
				this.transitionsOff()
			}

			try {
				if (this.$route.name === 'vote' && !this.$route.params.id) {
					throw new Error('No pollId for vote page')
				}

				if (this.$route.name === 'publicVote') {
					dispatches.push('share/get')
				} else if (this.$route.name === 'vote') {
					dispatches.push('shares/list')
				}

				dispatches.push(
					'poll/get',
					'comments/list',
					'options/list',
					'votes/list',
					'subscription/get',
				)

				const requests = dispatches.map((dispatches) => this.$store.dispatch(dispatches))
				await Promise.all(requests)

			} catch {
				this.$router.replace({ name: 'notfound' })
			} finally {
				this.loading = false
				this.transitionsOn()
			}
		},

		async updatePolls() {
			const dispatches = []
			if (this.$route.name === 'publicVote') {
				return
			}

			dispatches.push('polls/list')

			if (getCurrentUser().isAdmin) {
				dispatches.push('pollsAdmin/load')
			}

			try {
				const requests = dispatches.map((dispatches) => this.$store.dispatch(dispatches))
				await Promise.all(requests)
			} catch {
				showError(t('polls', 'Error loading poll list'))
			}
		},
	},
}

</script>

<style  lang="scss">

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
