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
import { NcContent } from '@nextcloud/vue'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
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
import { useSessionStore } from './stores/session.ts'
import { usePreferencesStore } from './stores/preferences.ts'
import { showSuccess } from '@nextcloud/dialogs'
import { debounce } from 'lodash'

export default {
	name: 'App',
	components: {
		NcContent,
		LoadingOverlay,
		UserSettingsDlg,
	},

	mixins: [watchPolls],

	beforeRouteUpdate(to, from, next) {
		Logger.debug('Route changed (update)', { from, to })
		this.loadContext()
		next()
	},

	data() {
		return {
			transitionClass: 'transitions-active',
			loading: false,
		}
	},

	computed: {
		...mapStores(
			useSessionStore,
			usePreferencesStore,
		),

		appClass() {
			return [
				this.transitionClass, {
					edit: this.sessionStore.pollPermissions.edit,
				},
			]
		},

		useNavigation() {
			return this.sessionStore.userStatus.isLoggedin
		},

		useSidebar() {
			return this.sessionStore.pollPermissions.edit
				|| this.sessionStore.pollPermissions.comment
				|| this.sessionStore.router.name === 'combo'
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

		subscribe('polls:poll:update', (payload) => {
			this.notify(payload)
		})
	},

	mounted() {
		this.loadContext(true)

	},

	beforeDestroy() {
		unsubscribe('polls:transitions:on')
		unsubscribe('polls:transitions:off')
		unsubscribe('polls:poll:updated')
	},

	methods: {
		notify: debounce(async function (payload) {
			if (payload.store === 'poll') {
				showSuccess(payload.message)
			}
		}, 1500),

		loadContext() {
			if (this.$route.name !== null) {
				this.sessionStore.setRouter(this.$route)
				this.sessionStore.load()
			}
			
			if (this.sessionStore.userStatus.isLoggedin) {
				this.preferencesStore.load()
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
