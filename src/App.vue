<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import {
	ref,
	computed,
	defineAsyncComponent,
	onMounted,
	onUnmounted,
	watchEffect,
} from 'vue'
import { useRoute } from 'vue-router'
import debounce from 'lodash/debounce'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import { t } from '@nextcloud/l10n'

import NcContent from '@nextcloud/vue/components/NcContent'

// import UserSettingsDlg from './components/Settings/UserSettingsDlg.vue'

import LoadingOverlay from './components/Base/modules/LoadingOverlay.vue'
import { usePollWatcher } from './composables/usePollWatcher'

import { useSessionStore } from './stores/session'
import { usePollStore } from './stores/poll'
import { usePollGroupsStore } from './stores/pollGroups'
import { showSuccess } from '@nextcloud/dialogs'
import { Event } from './Types'

import '@nextcloud/dialogs/style.css'
import './assets/scss/vars.scss'
import './assets/scss/hacks.scss'
import './assets/scss/print.scss'
import './assets/scss/transitions.scss'
import './assets/scss/markdown.scss'
import './assets/scss/globals.scss'

usePollWatcher()

const route = useRoute()
const sessionStore = useSessionStore()
const pollStore = usePollStore()
const pollGroupsStore = usePollGroupsStore()

const transitionClass = ref('transitions-active')

const appClass = computed(() => [
	transitionClass.value,
	{
		edit: pollStore.permissions.edit,
	},
])

const UserSettingsDlg = defineAsyncComponent(
	() => import('./components/Settings/UserSettingsDlg.vue'),
)

const useNavigation = computed(() => sessionStore.userStatus.isLoggedin)
const useSidebar = computed(
	() =>
		pollStore.permissions.edit
		|| pollStore.permissions.comment
		|| route.meta.comboPage
		|| (route.meta.groupPage
			&& pollGroupsStore.currentPollGroup?.owner.id
				=== sessionStore.currentUser.id),
)

/**
 * Turn off transitions
 */
function transitionsOn() {
	transitionClass.value = 'transitions-active'
}

/**
 * Turn on transitions
 *
 * @param delay - optional delay
 */
function transitionsOff(delay: number) {
	transitionClass.value = ''
	if (delay) {
		setTimeout(() => {
			transitionClass.value = 'transitions-active'
		}, delay)
	}
}

const debouncedNotify = debounce(async function (payload: {
	store: string
	message: string
}) {
	if (payload.store === 'poll') {
		showSuccess(payload.message)
	}
}, 1500)

function onTransitionsOff(delay: number) {
	transitionsOff(delay)
}

function onTransitionsOn() {
	transitionsOn()
}

watchEffect(() => {
	document.title = sessionStore.windowTitle
})

onMounted(() => {
	subscribe(Event.TransitionsOff, onTransitionsOff)
	subscribe(Event.TransitionsOn, onTransitionsOn)
	subscribe(Event.UpdatePoll, debouncedNotify)
})

onUnmounted(() => {
	unsubscribe(Event.TransitionsOff, onTransitionsOff)
	unsubscribe(Event.TransitionsOn, onTransitionsOn)
	unsubscribe(Event.UpdatePoll, debouncedNotify)
})
</script>

<template>
	<NcContent app-name="polls" :class="appClass">
		<router-view v-if="useNavigation" name="navigation" />
		<router-view />
		<router-view v-if="useSidebar" name="sidebar" />
		<UserSettingsDlg />
		<LoadingOverlay
			:show="sessionStore.navigationStatus === 'loading'"
			:name="t('polls', 'Loading…')"
			teleport-to="#content" />
	</NcContent>
</template>

<style lang="scss">
// remove public user menu from header
#public-page-user-menu {
	display: none;
}

#body-public a {
	font-weight: 600;
}
</style>
