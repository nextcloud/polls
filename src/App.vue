<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcContent app-name="polls" :class="appClass">
		<router-view v-if="useNavigation" name="navigation" />
		<router-view />
		<router-view v-if="useSidebar" name="sidebar" />
		<LoadingOverlay v-if="loading" />
		<UserSettingsDlg />
	</NcContent>
</template>

<script>
import { getCurrentUser } from '@nextcloud/auth'
import { NcContent } from '@nextcloud/vue'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import { mapState, mapActions } from 'vuex'
import '@nextcloud/dialogs/style.css'
import './assets/scss/colors.scss'
import './assets/scss/hacks.scss'
import './assets/scss/print.scss'
import './assets/scss/transitions.scss'
import './assets/scss/markdown.scss'
import { watchPolls } from './mixins/watchPolls.js'
import UserSettingsDlg from './components/Settings/UserSettingsDlg.vue'
import LoadingOverlay from './components/Base/modules/LoadingOverlay.vue'
import { Logger } from './helpers/index.js'
import { mapStores } from 'pinia';
import { useRouterStore } from './stores/router.ts'
import { usePollStore } from './stores/poll.ts'
import { useOptionsStore } from './stores/options.ts'
import { useVotesStore } from './stores/votes.ts'
import { useCommentsStore } from './stores/comments.ts'
import { useSubscriptionStore } from './stores/subscription.ts'
import { useActivityStore } from './stores/activity.ts'
import { useSharesStore } from './stores/shares.ts'
import { useAclStore } from './stores/acl.ts'
import { usePollsAdminStore } from './stores/pollsAdmin.ts'


export default {
	name: 'App',
	components: {
		NcContent,
		LoadingOverlay,
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
		...mapStores(
			useRouterStore,
			usePollStore,
			useOptionsStore,
			useVotesStore,
			useCommentsStore,
			useSubscriptionStore,
			useActivityStore,
			useSharesStore,
			useAclStore,
			usePollsAdminStore,
		),
		...mapState({
			permissions: (state) => state.poll.permissions,
		}),

		appClass() {
			return [
				this.transitionClass, {
					edit: this.permissions.edit,
				},
			]
		},

		useNavigation() {
			return !!getCurrentUser()
		},

		useSidebar() {
			return this.permissions.edit
				|| this.permissions.comment
				|| this.$route.name === 'combo'
		},
	},

	watch: {
		$route(to, from) {
			Logger.debug('Route changed', { from, to })
			this.loadContext()
			this.watchPolls()
		},
	},

	created() {
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

	mounted() {
		this.loadContext(true)

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
			loadAcl: 'acl/get',
			loadSettings: 'settings/get',
		}),

		loadContext(silent) {
			if (this.$route.name !== null) {
				this.loadAcl()
				this.routerStore.set(this.$route)
			}
			
			if (getCurrentUser()) {
				this.loadSettings()
				
				if (this.$route.name === 'list') {
					this.setFilter(this.$route.params.type)
				}
			}
			
			if (this.$route.name === 'vote' || this.$route.name === 'publicVote') {
				this.loadPoll(silent)
			}
		},

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
			Logger.debug('Updates detected', { stores })

			let dispatches = [
				'activity/list',
				'appSettings/get',
				'acl/get',
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
			// TODO: replace call to loadPiniaStores with loadStoresProxy after finishing pinia migration
			return this.loadStoresProxy(dispatches)
			// return Promise.all(dispatches.map((dispatches) => this.$store.dispatch(dispatches)))
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

				// replace call to loadStoresProxy with loadPiniaStores after finishing pinia migration
				await this.loadStoresProxy(dispatches)
			} catch {
				this.$router.replace({ name: 'notfound' })
			} finally {
				this.loading = false
				this.transitionsOn()
			}
		},

		// TODO: remove this function after finishing pinia migration
		async loadStoresProxy(dispatches) {
			await this.loadVuexStores(dispatches)
			Logger.debug('Loaded vuex stores', { dispatches })	
			await this.LoadPiniaStores(dispatches)
			Logger.debug('Loaded piniastores', { dispatches })	
		},

		async loadVuexStores(dispatches) {
			Logger.debug('Loading vuex stores', { dispatches })
			const requests = dispatches.map((dispatches) => this.$store.dispatch(dispatches))
			return Promise.all(requests)
		},

		async LoadPiniaStores(dispatches) {
			Logger.debug('Loading pinia stores', { dispatches })
			return Promise.all((dispatches) => {
				if (dispatches.includes('poll/get')) this.pollStore.load()
				if (dispatches.includes('acl/get')) this.aclStore.load()
				if (dispatches.includes('options/list')) this.optionsStore.load()
				if (dispatches.includes('votes/list')) this.votesStore.load()
				if (dispatches.includes('comments/list')) this.commentsStore.load()
				if (dispatches.includes('subscription/get')) this.subscriptionStore.load()
				if (dispatches.includes('activity/list')) this.activityStore.load()
				if (dispatches.includes('shares/list')) this.sharesStore.load()
				if (dispatches.includes('pollsAdmin/list')) this.pollsAdminStore.load()
			})
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
./stores/router.ts