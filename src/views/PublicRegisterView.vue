<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { DateTime } from 'luxon'
import { useRoute, useRouter } from 'vue-router'
import debounce from 'lodash/debounce'
import { showError } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
import { t, n } from '@nextcloud/l10n'
import { getDatesFromOption } from '../composables/optionDateTime'

import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcModal from '../components/Base/modules/CustomNcModal.vue'
import MagnifyExpandIcon from 'vue-material-design-icons/MagnifyExpand.vue'
import CalendarBlankOutlineIcon from 'vue-material-design-icons/CalendarBlankOutline.vue'
import FormatListBulletedSquareIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'

import InputDiv from '../components/Base/modules/InputDiv.vue'
import MarkDownDescription from '../components/Poll/MarkDownDescription.vue'
import OptionItem from '../components/Options/OptionItem.vue'
import PollInfoLine from '../components/Poll/PollInfoLine.vue'
import PublicFooter from '../components/Public/PublicFooter.vue'
import { InlineLink } from '../helpers/modules/InlineLink'
import { setCookie } from '../helpers/modules/cookieHelper'
import { ValidatorAPI, PublicAPI } from '../Api'
import { useSessionStore } from '../stores/session'
import { useOptionsStore } from '../stores/options'
import { usePollStore } from '../stores/poll'

import type { AxiosError } from '@nextcloud/axios'
import type { SignalingType } from '../Types'

const route = useRoute()
const router = useRouter()

const sessionStore = useSessionStore()
const optionsStore = useOptionsStore()
const pollStore = usePollStore()

const COOKIE_LIFETIME = 30
const MAX_OPTIONS = 5

const sendRegistration = ref(false)
const saveCookie = ref(true)
const descriptionExpanded = ref(false)
const optionsExpanded = ref(false)

const previewOptions = computed(() => optionsStore.options.slice(0, MAX_OPTIONS))

function optionLabel(option: (typeof optionsStore.options)[0]): string {
	if (pollStore.type === 'datePoll') {
		const { optionStart, optionEnd, isSameTime, isFullDays, isSameDay } =
			getDatesFromOption(option)
		const fmt = isFullDays ? DateTime.DATE_MED : DateTime.DATETIME_MED
		const start = optionStart.toLocaleString(fmt)
		if (isSameTime || (isFullDays && isSameDay)) return start
		return `${start} – ${optionEnd.toLocaleString(fmt)}`
	}
	return option.text
}

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
		<div class="register-view__col">
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

			<div
				v-if="sessionStore.appSettings.useLogin"
				class="register-view__login">
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
		</div>

		<div class="register-view__col">
			<div
				v-if="pollStore.configuration.description"
				class="register-view__description"
				role="button"
				:aria-label="t('polls', 'Expand description')"
				@click="descriptionExpanded = true">
				<span class="register-view__description-expand" aria-hidden="true">
					<MagnifyExpandIcon :size="20" />
				</span>
				<MarkDownDescription />
			</div>

			<div
				v-if="optionsStore.options.length"
				class="register-view__options"
				role="button"
				:aria-label="t('polls', 'Expand options')"
				@click="optionsExpanded = true">
				<span class="register-view__expand-btn" aria-hidden="true">
					<MagnifyExpandIcon :size="20" />
				</span>
				<h3>{{ t('polls', 'Available options') }}</h3>
				<ul>
					<li
						v-for="option in previewOptions"
						:key="option.id"
						class="register-view__option-item">
						<CalendarBlankOutlineIcon
							v-if="pollStore.type === 'datePoll'"
							:size="16" />
						<FormatListBulletedSquareIcon v-else :size="16" />
						{{ optionLabel(option) }}
					</li>
				</ul>
				<p
					v-if="optionsStore.options.length > MAX_OPTIONS"
					class="register-view__options-more">
					{{
						n(
							'polls',
							'+ %n more option',
							'+ %n more options',
							optionsStore.options.length - MAX_OPTIONS,
						)
					}}
				</p>
			</div>
		</div>
		<PublicFooter />
	</NcAppContent>

	<NcModal
		v-if="optionsExpanded"
		:name="t('polls', 'Available options')"
		size="normal"
		close-on-click-outside
		@close="optionsExpanded = false">
		<ul class="register-view__options-modal">
			<OptionItem
				v-for="option in optionsStore.options"
				:key="option.id"
				:option="option"
				tag="li" />
		</ul>
	</NcModal>

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
	background: none !important;
	padding-bottom: 3rem !important;
	margin: 0 auto !important;
}

.register-view {
	display: flex;
	flex-wrap: wrap;
	justify-content: center;
	align-content: flex-start;
	gap: 1rem;
	padding: 1.5rem 1rem;
	max-width: 100rem;

	.register-view__col {
		flex: 1 1 27rem;
		display: flex;
		flex-direction: column;
		gap: 1rem;
		min-width: 0;
	}

	.register-view__col > * {
		border-radius: 16px;
		box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
		backdrop-filter: blur(5px);
		-webkit-backdrop-filter: blur(5px);
		border: 1px solid rgb(from var(--color-border) r g b / 0.6);
		padding: 0.5rem;
		background-color: var(--bg-glass);
	}

	.register-view__header {
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
		display: flex;
		flex-direction: column;
		gap: 0.5rem;

		h3 {
			margin: 0 0 0.5rem;
		}

		[class*='section__'] {
			margin: 0.25rem 0;
		}
	}

	.register-view__description {
		max-height: 13rem;
		position: relative;
		min-height: 8rem;
		overflow: hidden;
		cursor: zoom-in;

		* {
			cursor: zoom-in;
		}

		.markdown-description {
			height: 100%;
			overflow: hidden;
			background: none;
		}

		.register-view__description-expand {
			position: absolute;
			top: 0.25rem;
			right: 0.25rem;
			z-index: 1;
			display: flex;
			align-items: center;
			justify-content: center;
			width: 44px;
			height: 44px;
			border-radius: var(--border-radius-large);
			background-color: var(--color-background-hover);
			border: 2px solid var(--color-border);
			color: var(--color-main-text);
			pointer-events: none;
			opacity: 0;
			transition: opacity 0.2s;
		}

		&:hover .register-view__description-expand {
			opacity: 1;
		}
	}

	.register-view__actions {
		display: flex;
		justify-content: end;
		align-items: center;
		margin-top: 0.5rem;
	}

	.register-view__options {
		position: relative;
		cursor: zoom-in;

		* {
			cursor: zoom-in;
		}

		h3 {
			margin: 0 0 0.5rem;
		}

		ul {
			list-style: none;
			padding: 0;
			margin: 0;
		}

		.register-view__option-item {
			display: flex;
			align-items: center;
			gap: 0.4rem;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
			padding: 0.25rem 0;
			border-bottom: 1px solid var(--color-border);
			background: transparent;

			&:last-child {
				border-bottom: none;
			}
		}

		.register-view__expand-btn {
			position: absolute;
			top: 0.25rem;
			right: 0.25rem;
			z-index: 1;
			display: flex;
			align-items: center;
			justify-content: center;
			width: 44px;
			height: 44px;
			border-radius: var(--border-radius-large);
			background-color: var(--color-background-hover);
			border: 2px solid var(--color-border);
			color: var(--color-main-text);
			pointer-events: none;
			opacity: 0;
			transition: opacity 0.2s;
		}

		&:hover .register-view__expand-btn {
			opacity: 1;
		}
		.register-view__options-more {
			text-align: center;
			font-weight: 600;
		}
	}
}

.register-view__options-modal {
	list-style: none;
	padding: 1rem;
	margin: 0;

	.register-view__option-item {
		padding: 0.4rem 0;
		border-bottom: 1px solid var(--color-border);

		&:last-child {
			border-bottom: none;
		}
	}
}

.modal-container__content .markdown-description {
	--markdown-description-bg: var(--color-main-background);
}
</style>
