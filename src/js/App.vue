<!--
  - @copyright Copyright (c) 2019 René Gieling <github@dartcafe.de>
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
	<NcContent app-name="polls" :class="appClass">
		<router-view v-if="getCurrentUser()" name="navigation" />
		<router-view />
		<router-view v-if="poll.acl.allowEdit || poll.acl.allowComment" name="sidebar" />
		<LoadingOverlay v-if="loading" />
		<UserSettingsDlg />
	</NcContent>
</template>

<script>
import UserSettingsDlg from './components/Settings/UserSettingsDlg.vue'
import { getCurrentUser } from '@nextcloud/auth'
import { NcContent } from '@nextcloud/vue'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import { mapState, mapActions } from 'vuex'
import '@nextcloud/dialogs/style.css'
import './assets/scss/colors.scss'
import './assets/scss/hacks.scss'
import './assets/scss/icons.scss'
import './assets/scss/print.scss'
import './assets/scss/transitions.scss'
import './assets/scss/markdown.scss'
import { watchPolls } from './mixins/watchPolls.js'

export default {
	name: 'App',
	components: {
		NcContent,
		LoadingOverlay: () => import('./components/Base/modules/LoadingOverlay.vue'),
		UserSettingsDlg,
	},

	mixins: [watchPolls],

	data() {
		return {
			transitionClass: 'transitions-active',
			loading: false,
			isLoggedin: !!getCurrentUser(),
			isAdmin: !!getCurrentUser()?.isAdmin,
		}
	},

	computed: {
		...mapState({
			settings: (state) => state.settings.user,
			appSettings: (state) => state.appSettings,
			poll: (state) => state.poll,
			allowEdit: (state) => state.poll.acl.allowEdit,
		}),

		appClass() {
			return [
				this.transitionClass, {
					edit: this.allowEdit,
				},
			]
		},
	},

	watch: {
		$route(to, from) {
			if (this.$route.name === 'list') {
				this.setFilter(this.$route.params.type)
			}
			this.loadPoll()
			this.watchPolls()
		},
	},

	created() {
		this.$store.dispatch('appSettings/get')
		if (getCurrentUser()) {
			this.$store.dispatch('settings/get')
			if (this.$route.params.id && !this.$route.params.token) {
				this.loadPoll(true)
			}
		}

		subscribe('polls:transitions:off', (delay) => {
			this.transitionsOff(delay)
		})

		subscribe('polls:transitions:on', () => {
			this.transitionsOn()
		})

		subscribe('polls:stores:load', (stores) => {
			this.loadStores(stores)
		})

		subscribe('polls:poll:load', (silent) => {
			this.loadPoll(silent)
		})

	},

	beforeDestroy() {
		this.cancelToken.cancel()
		unsubscribe('polls:poll:load')
		unsubscribe('polls:transitions:on')
		unsubscribe('polls:transitions:off')
	},

	methods: {
		...mapActions({
			setFilter: 'polls/setFilter',
		}),

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

		async loadStores(stores) {
			console.debug('[polls]', 'Updates detected', stores)

			let dispatches = [
				'activity/list',
				'appSettings/get',
			]

			stores.forEach((item) => {
				if (item.table === 'polls') {

					// If user is an admin, also load admin list
					if (this.isAdmin) dispatches = [...dispatches, 'pollsAdmin/list']

					// if user is an authorized user load polls list and combo
					if (this.isLoggedin) dispatches = [...dispatches, `${item.table}/list`, 'combo/cleanUp']

					// if current poll is affected, load current poll configuration
					if (item.pollId === this.$store.state.poll.id) {
						dispatches = [...dispatches, 'poll/get']
					}

				} else if (!this.isLoggedin && (item.table === 'shares')) {
					// if current user is guest and table is shares only reload current share
					dispatches = [...dispatches, 'share/get']
				} else {
					// otherwise just load particulair store
					dispatches = [...dispatches, `${item.table}/list`]
				}
			})
			dispatches = [...new Set(dispatches)] // remove duplicates and add combo
			return Promise.all(dispatches.map((dispatches) => this.$store.dispatch(dispatches)))
		},

		async loadPoll(silent) {
			if (!silent) {
				this.loading = true
				this.transitionsOff()
			}

			try {
				if (this.$route.name === 'vote' && !this.$route.params.id) {
					throw new Error('No pollId for vote page')
				}
				const dispatches = [
					'poll/get',
					'comments/list',
					'options/list',
					'votes/list',
					'subscription/get',
				]

				if (this.$route.name === 'publicVote') {
					dispatches.push('share/get')
				} else if (this.$route.name === 'vote') {
					dispatches.push('shares/list')
					dispatches.push('activity/list')
				}

				const requests = dispatches.map((dispatches) => this.$store.dispatch(dispatches))
				await Promise.all(requests)

			} catch {
				this.$router.replace({ name: 'notfound' })
			} finally {
				this.loading = false
				this.transitionsOn()
			}
		},

	},
}

</script>

<style lang="scss">
.app-content {
	display: flex;
	flex-direction: column;
	padding: 0px 8px;
	row-gap: 8px;
}

// global areas settings
[class*=' area__'],
[class^='area__'] {
	padding: 4px 0px;
	background-color: var(--color-main-background);
	border-radius: var(--border-radius);
	min-width: 270px;
}

// special settings for header area
[class*=' area__header'],
[class^='area__header'] {
	position: sticky;
	top: 0;
	background-color: var(--color-main-background);
	border-bottom: 1px solid var(--color-border);
	z-index: 9;
	margin-left: -8px;
	margin-right: -8px;
	padding-right: 8px;
	padding-left: 56px;
}

// global modal settings
.modal__content {
	padding: 14px;
	display: flex;
	flex-direction: column;
	color: var(--color-main-text);
}

.modal__buttons__spacer {
	flex: 1;
}

.modal__buttons {
	display: flex;
	gap: 8px;
	justify-content: flex-end;
	flex-wrap: wrap-reverse;
	align-items: center;
	margin-top: 36px;

	.left {
		display: flex;
		flex: 1;
		gap: 8px;
	}

	.right {
		display: flex;
		flex: 0;
		justify-content: flex-end;
		gap: 8px;
	}

	.button {
		margin-left: 10px;
		margin-right: 0;
	}
}

.modal__buttons__link {
	text-decoration: underline;
}

</style>
