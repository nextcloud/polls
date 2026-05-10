<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import debounce from 'lodash/debounce'
import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'

import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'

import InputDiv from '@/components/Base/modules/InputDiv.vue'
import { setCookie } from '@/helpers/modules/cookieHelper'
import { ValidatorAPI, PublicAPI } from '@/Api'
import { useSessionStore } from '@/stores/session'

import type { AxiosError } from '@nextcloud/axios'
import type { SignalingType } from '@/Types'

const route = useRoute()
const router = useRouter()

const sessionStore = useSessionStore()

const COOKIE_LIFETIME = 30

const sendRegistration = ref(false)
const saveCookie = ref(true)

const offerCookies = computed(() => sessionStore.share.type === 'public')

function updateCookie(value: string): void {
	const cookieExpiration = COOKIE_LIFETIME * 24 * 60 * 1000
	setCookie(route.params.token.toString(), value, cookieExpiration)
}

const checkStatus = ref<{
	email: SignalingType
	userName: SignalingType
}>({
	email: 'empty',
	userName: 'missing',
})

const userName = ref('')
const userNameHint = computed(() => {
	if (checkStatus.value.userName === 'checking') {
		return t('polls', 'Checking name …')
	}
	if (checkStatus.value.userName === 'missing') {
		return t('polls', 'A name is required.')
	}
	if (checkStatus.value.userName === 'invalid') {
		return t('polls', 'The name {username} is invalid or reserved.', {
			username: userName.value,
		})
	}
	return ''
})

const validatePublicUsername = debounce(async function (): Promise<void> {
	if (userName.value.length < 1) {
		checkStatus.value.userName = 'missing'
		return
	}

	checkStatus.value.userName = 'checking'

	try {
		await ValidatorAPI.validateName(route.params.token, userName.value)
		checkStatus.value.userName = 'valid'
	} catch (error) {
		if ((error as AxiosError).code === 'ERR_CANCELED') {
			return
		}

		checkStatus.value.userName = 'invalid'

		if ((error as AxiosError).code === 'ERR_BAD_REQUEST') {
			return
		}
		throw error
	}
}, 500)

const emailAddress = ref('')

const emailAddressHint = computed(() => {
	if (checkStatus.value.email === 'checking') {
		return t('polls', 'Checking email address …')
	}
	if (checkStatus.value.email === 'missing') {
		return t('polls', 'An email address is required.')
	}
	if (checkStatus.value.email === 'invalid') {
		return t('polls', 'Invalid email address.')
	}
	if (sessionStore.share.type === 'public') {
		if (checkStatus.value.email === 'valid') {
			return t(
				'polls',
				'You will receive your personal link after clicking "OK".',
			)
		}
		return t(
			'polls',
			'Enter your email address to get your personal access link.',
		)
	}
	return ''
})

const validateEmailAddress = debounce(async function (): Promise<void> {
	if (emailAddress.value.length < 1) {
		checkStatus.value.email =
			sessionStore.share.publicPollEmail === 'mandatory' ? 'missing' : 'empty'
		return
	}

	checkStatus.value.email = 'checking'

	try {
		await ValidatorAPI.validateEmailAddress(emailAddress.value)
		checkStatus.value.email = 'valid'
	} catch (error) {
		if ((error as AxiosError).code === 'ERR_CANCELED') {
			return
		}
		if ((error as AxiosError).code === 'ERR_BAD_REQUEST') {
			checkStatus.value.email = 'invalid'
			return
		}
		throw error
	}
}, 500)

const registrationIsValid = computed(
	() =>
		checkStatus.value.userName === 'valid'
		&& (checkStatus.value.email === 'valid'
			|| (emailAddress.value.length === 0
				&& sessionStore.share.publicPollEmail !== 'mandatory')),
)

function routeToPersonalShare(token: string): void {
	router.push({
		name: 'publicVote',
		params: { token },
		replace: true,
	})
}

async function submitRegistration(): Promise<void> {
	if (!registrationIsValid.value || sendRegistration.value) {
		return
	}

	sendRegistration.value = true

	try {
		const response = await PublicAPI.register(
			route.params.token as string,
			userName.value,
			emailAddress.value,
		)

		if (saveCookie.value) {
			updateCookie(response.data.share.token)
		}

		routeToPersonalShare(response.data.share.token)

		if (
			sessionStore.currentUser.emailAddress
			&& !sessionStore.share.invitationSent
		) {
			showError(
				t('polls', 'Email could not be sent to {emailAddress}', {
					emailAddress: sessionStore.currentUser.emailAddress,
				}),
			)
		}
	} catch (error) {
		if ((error as AxiosError)?.code === 'ERR_CANCELED') {
			return
		}
		showError(t('polls', 'Error registering to poll', { error }))
		throw error
	} finally {
		sendRegistration.value = false
	}
}

const disableSubmit = computed(
	() =>
		!registrationIsValid.value
		|| checkStatus.value.userName === 'checking'
		|| sendRegistration.value,
)

onMounted(() => {
	userName.value = sessionStore.currentUser.displayName
	validatePublicUsername()

	emailAddress.value = sessionStore.currentUser.emailAddress
	validateEmailAddress()
})
</script>

<template>
	<h3>{{ t('polls', 'Register to this poll') }}</h3>

	<InputDiv
		v-model="userName"
		class="section__username"
		:signaling-class="checkStatus.userName"
		:placeholder="t('polls', 'Enter your name or a nickname')"
		:helper-text="userNameHint"
		focus
		@input="validatePublicUsername()"
		@submit="submitRegistration()" />

	<InputDiv
		v-if="sessionStore.share.publicPollEmail !== 'disabled'"
		v-model="emailAddress"
		class="section__email"
		:signaling-class="checkStatus.email"
		:placeholder="
			sessionStore.share.publicPollEmail === 'mandatory'
				? t('polls', 'Email address (mandatory)')
				: t('polls', 'Email address (optional)')
		"
		:helper-text="emailAddressHint"
		type="email"
		inputmode="email"
		@input="validateEmailAddress()"
		@submit="submitRegistration()" />

	<NcCheckboxRadioSwitch v-if="offerCookies" v-model="saveCookie">
		{{ t('polls', 'Remember me for 30 days') }}
	</NcCheckboxRadioSwitch>

	<div class="register_actions">
		<NcButton
			:variant="'primary'"
			:disabled="disableSubmit"
			@click="submitRegistration()">
			<template #default>
				{{ t('polls', 'OK') }}
			</template>
		</NcButton>
	</div>
</template>

<style lang="scss" scoped>
.register_actions {
	display: flex;
	justify-content: end;
	align-items: center;
	margin-top: 0.5rem;
}
</style>
