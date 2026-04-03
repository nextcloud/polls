<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import debounce from 'lodash/debounce'
import { showError } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
import { t } from '@nextcloud/l10n'

import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcModal from '../components/Base/modules/CustomNcModal.vue'

import InputDiv from '../components/Base/modules/InputDiv.vue'
import MarkDownDescription from '../components/Poll/MarkDownDescription.vue'
import PollInfoLine from '../components/Poll/PollInfoLine.vue'
import { InlineLink } from '../helpers/modules/InlineLink'
import { SimpleLink } from '../helpers/modules/SimpleLink'
import { setCookie } from '../helpers/modules/cookieHelper'
import { ValidatorAPI, PublicAPI } from '../Api'
import { useSessionStore } from '../stores/session'
import { usePollStore } from '../stores/poll'

import type { AxiosError } from '@nextcloud/axios'
import type { SignalingType } from '../Types'

const route = useRoute()
const router = useRouter()

const sessionStore = useSessionStore()
const pollStore = usePollStore()

const COOKIE_LIFETIME = 30

const sendRegistration = ref(false)
const saveCookie = ref(true)
const descriptionExpanded = ref(false)

const loginLink = computed(() => {
	const redirectUrl = router.resolve({
		name: 'publicVote',
		params: { token: route.params.token },
	}).href

	return `${generateUrl('/login')}?redirect_url=${redirectUrl}`
})

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
	<NcAppContent class="register-view">
		<div class="register-view__header">
			<h2 class="register-view__title">
				{{ pollStore.configuration.title }}
			</h2>
			<PollInfoLine />
		</div>

		<div class="register-view__form">
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

			<div class="register-view__actions">
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

		<div v-if="sessionStore.appSettings.showLogin" class="register-view__login">
			<!-- TRANSLATORS Position [link]...[/link] around the word to link. Will be replaced with <a href="..."> and </a> -->
			<InlineLink
				:text="
					t(
						'polls',
						'Or [link]login[/link] if you are a member of this site.',
					)
				"
				:href="loginLink" />
		</div>
		<div
			v-if="pollStore.configuration.description"
			class="register-view__description"
			role="button"
			:aria-label="t('polls', 'Expand description')"
			@click="descriptionExpanded = true">
			<MarkDownDescription />
		</div>
	</NcAppContent>

	<NcModal
		v-if="descriptionExpanded"
		:name="pollStore.configuration.title"
		size="large"
		close-on-click-outside
		@close="descriptionExpanded = false">
		<MarkDownDescription />
	</NcModal>
</template>

<style lang="scss">
.app-content.register-view {
	--bg-glass: rgb(from var(--color-background-assistant) r g b / 0.7);
	margin: auto !important;
	background: none !important;
}

.register-view {
	display: grid;
	grid-template-columns: repeat(
		auto-fit,
		minmax(calc((var(--cap-width) / 2) - 2rem), 1fr)
	);
	// Define 5 rows for properly description expanding
	grid-template-rows: auto auto auto auto 1fr;
	grid-auto-flow: column;
	gap: 1rem;
	width: 100%;
	max-width: var(--cap-width);
	padding: 1.5rem 1rem;

	> * {
		border-radius: 16px;
		box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
		backdrop-filter: blur(5px);
		-webkit-backdrop-filter: blur(5px);
		border: 1px solid rgb(from var(--color-border) r g b / 0.6);
		padding: 0.5rem;
		background-color: var(--bg-glass);
	}

	.register-view__header {
		grid-column-start: 1;
		display: flex;
		flex-direction: column;
		gap: 0.25rem;
	}

	.register-view__title {
		font-size: 1.5rem;
		font-weight: bold;
		margin: 0;
	}

	.register-view__form {
		grid-column-start: 1;
		display: flex;
		flex-direction: column;
		gap: 0.5rem;

		h3 {
			margin: 0 0 0.5rem;
		}

		[class*='section__'] {
			margin: 4px 0;
		}
	}

	.register-view__login {
		grid-column-start: 1;
		margin-top: 0.5rem;
	}

	.register-view__description {
		grid-column-end: -1;
		grid-row: span 4;
		position: relative;
		min-height: 8rem;
		overflow: hidden;
		cursor: zoom-in;

		.markdown-description {
			height: 100%;
			overflow: hidden;
			background: none;
		}

		// Gradient hint that more content exists — opaque overlay div,
		// no transparency stacking issues.
		&::after {
			content: '';
			position: absolute;
			inset: auto 0 0 0;
			height: 3rem;
			background: linear-gradient(
				rgb(from var(--color-background-assistant) r g b / 0),
				var(--color-background-assistant)
			);
			pointer-events: none;
		}
	}

	.register-view__actions {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-top: 0.5rem;
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
}

.modal-container__content .markdown-description {
	--markdown-description-bg: var(--color-main-background);
}
</style>
