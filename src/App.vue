<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { debounce } from 'lodash'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'

import NcContent from '@nextcloud/vue/components/NcContent'

import UserSettingsDlg from './components/Settings/UserSettingsDlg.vue'
import LoadingOverlay from './components/Base/modules/LoadingOverlay.vue'

import { useSessionStore } from './stores/session.ts'
import { usePollStore } from './stores/poll.ts'
import { showSuccess } from '@nextcloud/dialogs'

import '@nextcloud/dialogs/style.css'
import './assets/scss/colors.scss'
import './assets/scss/hacks.scss'
import './assets/scss/print.scss'
import './assets/scss/transitions.scss'
import './assets/scss/markdown.scss'
import { Event } from './Types/index.ts'

const sessionStore = useSessionStore()
const pollStore = usePollStore()
const transitionClass = ref('transitions-active')
const loading = ref(false)

const appClass = computed(() => [
	transitionClass.value,
	{
		edit: pollStore.permissions.edit,
	},
])

const useNavigation = computed(() => sessionStore.userStatus.isLoggedin)
const useSidebar = computed(
	() =>
		pollStore.permissions.edit
		|| pollStore.permissions.comment
		|| sessionStore.route.name === 'combo',
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

/**
 *
 * @param payload
 * @param payload.store
 * @param payload.message
 */
function notify(payload: { store: string; message: string }) {
	debounce(async function () {
		if (payload.store === 'poll') {
			showSuccess(payload.message)
		}
	}, 1500)
}

onMounted(() => {
	subscribe(Event.TransitionsOff, (delay) => {
		transitionsOff(delay)
	})

	subscribe(Event.TransitionsOn, () => {
		transitionsOn()
	})

	subscribe(Event.UpdatePoll, (payload) => {
		notify(payload)
	})
})

onUnmounted(() => {
	unsubscribe(Event.TransitionsOn, () => {
		transitionsOn()
	})
	unsubscribe(Event.TransitionsOff, () => {})
	unsubscribe(Event.UpdatePoll, () => {})
})
</script>

<template>
	<NcContent app-name="polls" :class="appClass">
		<router-view v-if="useNavigation" name="navigation" />
		<router-view />
		<router-view v-if="useSidebar" name="sidebar" />
		<LoadingOverlay v-if="loading" />
		<UserSettingsDlg />
	</NcContent>
</template>

<style lang="scss">
.app-content {
	display: flex;
	flex-direction: column;
	padding: 0px 8px;
	row-gap: 8px;

	.clamped {
		display: -webkit-box !important;
		-webkit-line-clamp: 2;
		line-clamp: 2;
		-webkit-box-orient: vertical;
		text-wrap: wrap;
		overflow: clip !important;
		text-overflow: ellipsis !important;
		padding: 0 !important;
	}
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
