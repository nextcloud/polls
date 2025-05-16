<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { debounce } from 'lodash'
import { showError } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
import { t } from '@nextcloud/l10n'

import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcButton from '@nextcloud/vue/components/NcButton'

import { InputDiv } from '../Base/index.ts'
import { SimpleLink, setCookie } from '../../helpers/index.ts'
import { ValidatorAPI, PublicAPI } from '../../Api/index.ts'
import { SignalingType, ShareType } from '../../Types'
import { useSessionStore } from '../../stores/session.ts'
import { usePollStore } from '../../stores/poll.ts'
import { PublicPollEmailConditions } from '../../stores/shares.ts'
import { AxiosError } from '@nextcloud/axios'

const route = useRoute()
const router = useRouter()

const emit = defineEmits(['close'])

const sessionStore = useSessionStore()
const pollStore = usePollStore()

const COOKIE_LIFETIME = 30
const checkStatus = ref({
	email: SignalingType.Empty,
	userName: SignalingType.Empty,
})

const sendRegistration = ref(false)
const userName = ref('')
const emailAddress = ref('')
const saveCookie = ref(true)

const registrationIsValid = computed(
	() =>
		checkStatus.value.userName === SignalingType.Valid
		&& (checkStatus.value.email === SignalingType.Valid
			|| (emailAddress.value.length === 0
				&& sessionStore.share.publicPollEmail
					!== PublicPollEmailConditions.Mandatory)),
)
const disableSubmit = computed(
	() =>
		!registrationIsValid.value
		|| checkStatus.value.userName === SignalingType.Checking
		|| sendRegistration.value,
)
const emailGeneratedStatus = computed(() =>
	checkStatus.value.email === SignalingType.Empty
		? sessionStore.share.publicPollEmail
		: checkStatus.value.email,
)
const offerCookies = computed(() => sessionStore.share.type === ShareType.Public)

const loginLink = computed(() => {
	const redirectUrl = router.resolve({
		name: 'publicVote',
		params: { token: route.params.token },
	}).href

	return `${generateUrl('/login')}?redirect_url=${redirectUrl}`
})

const userNameHint = computed(() => {
	if (checkStatus.value.userName === SignalingType.Checking) {
		return t('polls', 'Checking name …')
	}
	if (checkStatus.value.userName === SignalingType.Empty) {
		return t('polls', 'A name is required.')
	}
	if (checkStatus.value.userName === SignalingType.InValid) {
		return t('polls', 'The name {username} is invalid or reserved.', {
			username: userName.value,
		})
	}
	return ''
})

const emailAddressHint = computed(() => {
	if (emailGeneratedStatus.value === 'checking') {
		return t('polls', 'Checking email address …')
	}
	if (emailGeneratedStatus.value === 'mandatory') {
		return t('polls', 'An email address is required.')
	}
	if (emailGeneratedStatus.value === 'invalid') {
		return t('polls', 'Invalid email address.')
	}
	if (sessionStore.share.type === ShareType.Public) {
		if (emailGeneratedStatus.value === 'valid') {
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

onMounted(() => {
	if (route.name === 'publicVote' && route.query.name) {
		userName.value = route.query.name.toString()
	} else {
		userName.value = sessionStore.currentUser.displayName
	}
	if (route.name === 'publicVote' && route.query.email) {
		emailAddress.value = route.query.email.toString()
	} else {
		emailAddress.value = sessionStore.currentUser.emailAddress
	}
})

/**
 *
 * @param token
 */
function routeToPersonalShare(token: string): void {
	if (route.params.token === token) {
		// if share was not a public share, but a personal share
		// (i.e. email shares allow to change personal data by fist entering of the poll),
		// just load the poll
		pollStore.load()
		closeModal()
	} else {
		// in case of a public share, redirect to the generated share
		router.push({
			name: 'publicVote',
			params: { token },
			replace: true,
		})
		closeModal()
	}
}

/**
 *
 * @param value - value to be stored in the cookie
 */
function updateCookie(value: string): void {
	const cookieExpiration = COOKIE_LIFETIME * 24 * 60 * 1000
	setCookie(route.params.token.toString(), value, cookieExpiration)
}

/**
 *
 */
function closeModal(): void {
	emit('close')
}

/**
 *
 */
function login(): void {
	window.location.assign(
		`${window.location.protocol}//${window.location.host}${loginLink.value}`,
	)
}

const validatePublicUsername = debounce(async function (): Promise<void> {
	if (userName.value.length < 1) {
		checkStatus.value.userName = SignalingType.Empty
		return
	}

	checkStatus.value.userName = SignalingType.Checking
	try {
		await ValidatorAPI.validateName(route.params.token, userName.value)
		checkStatus.value.userName = SignalingType.Valid
	} catch (error) {
		if ((error as AxiosError).code === 'ERR_CANCELED') {
			return
		}
		if ((error as AxiosError).code === 'ERR_BAD_REQUEST') {
			checkStatus.value.userName = SignalingType.InValid
			return
		}
		throw error
	}
}, 500)

const validateEmailAddress = debounce(async function (): Promise<void> {
	if (emailAddress.value.length < 1) {
		checkStatus.value.email = SignalingType.Empty
		return
	}

	checkStatus.value.email = SignalingType.Checking
	try {
		await ValidatorAPI.validateEmailAddress(emailAddress.value)
		checkStatus.value.email = SignalingType.Valid
	} catch (error) {
		if ((error as AxiosError).code === 'ERR_CANCELED') {
			return
		}
		if ((error as AxiosError).code === 'ERR_BAD_REQUEST') {
			checkStatus.value.email = SignalingType.InValid
			return
		}
		throw error
	}
}, 500)

/**
 *
 */
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

		if (saveCookie.value && route.name === 'publicVote') {
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
</script>

<template>
	<div class="modal__content">
		<div class="modal__registration">
			<div class="registration__registration">
				<h2>{{ t('polls', 'Guest participants') }}</h2>
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
					v-if="
						sessionStore.share.publicPollEmail
						!== PublicPollEmailConditions.Disabled
					"
					v-model="emailAddress"
					class="section__email"
					:signaling-class="checkStatus.email"
					:placeholder="
						sessionStore.share.publicPollEmail
						=== PublicPollEmailConditions.Mandatory
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

				<div class="modal__buttons">
					<div class="left">
						<div class="legal_links">
							<SimpleLink
								v-if="sessionStore.appSettings.finalImprintUrl"
								:href="sessionStore.appSettings.finalImprintUrl"
								target="_blank"
								:name="t('polls', 'Legal Notice')" />
							<SimpleLink
								v-if="sessionStore.appSettings.finalPrivacyUrl"
								:href="sessionStore.appSettings.finalPrivacyUrl"
								target="_blank"
								:name="t('polls', 'Privacy policy')" />
						</div>
					</div>
					<div class="right">
						<NcButton @click="closeModal">
							<template #default>
								{{ t('polls', 'Cancel') }}
							</template>
						</NcButton>

						<NcButton
							:variant="'primary'"
							:disabled="disableSubmit"
							@click="submitRegistration()">
							<template #default>
								{{ t('polls', 'OK') }}
							</template>
						</NcButton>
					</div>
				</div>
			</div>

			<div
				v-if="sessionStore.appSettings.showLogin"
				class="registration__login">
				<h2>{{ t('polls', 'Registered accounts') }}</h2>
				<NcButton wide @click="login()">
					<template #default>
						{{ t('polls', 'Login') }}
					</template>
				</NcButton>
				<div>
					{{
						t(
							'polls',
							'You can also log in and participate with your regular account.',
						)
					}}
				</div>
				<div>
					{{ t('polls', 'Otherwise participate as a guest participant.') }}
				</div>
			</div>
		</div>
	</div>
</template>

<style lang="scss">
.section__optin {
	a {
		text-decoration: underline;
	}
}

.modal__registration {
	display: flex;
	flex-wrap: wrap;
	overflow: hidden;
	& > div {
		display: flex;
		flex-direction: column;
		flex: 1 auto;
		min-width: 140px;
		padding: 24px;
		border-top: 1px solid;
		border-right: 1px solid;
		margin-top: -2px;
		margin-right: -2px;
		> button {
			margin: 8px 0;
		}
	}

	.registration__login {
		flex: 1 180px;
	}
	.registration__registration {
		flex: 1 480px;
	}
}

[class*='section__'] {
	margin: 4px 0;
}

.modal__content {
	.enter__name,
	.enter__email {
		margin-bottom: 12px;
	}
}

.legal_links {
	a {
		color: var(--color-text-maxcontrast);
		font-weight: bold;
		white-space: nowrap;
		padding: 10px;
		margin: -10px;

		&:hover,
		&:active {
			color: var(--color-main-text);
			&::after {
				color: var(--color-text-maxcontrast);
			}
		}

		&:after {
			content: '·';
			padding: 0 4px;
		}

		&:last-child:after {
			content: '';
		}
	}
}
</style>
