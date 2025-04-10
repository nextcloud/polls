<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { debounce } from 'lodash'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'
import { t } from '@nextcloud/l10n'

import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActionCheckbox from '@nextcloud/vue/components/NcActionCheckbox'
import NcActionInput from '@nextcloud/vue/components/NcActionInput'
import NcActionSeparator from '@nextcloud/vue/components/NcActionSeparator'

import SettingsIcon from 'vue-material-design-icons/Cog.vue'
import SendLinkPerEmailIcon from 'vue-material-design-icons/LinkVariant.vue'
import DeleteIcon from 'vue-material-design-icons/Delete.vue'
import ClippyIcon from 'vue-material-design-icons/ClipboardArrowLeftOutline.vue'
import ResetVotesIcon from 'vue-material-design-icons/Undo.vue'
import EditAccountIcon from 'vue-material-design-icons/AccountEdit.vue'
import LogoutIcon from 'vue-material-design-icons/Logout.vue'
import EditEmailIcon from 'vue-material-design-icons/EmailEditOutline.vue'
import ListViewIcon from 'vue-material-design-icons/ViewListOutline.vue' // view-sequential-outline
import TableViewIcon from 'vue-material-design-icons/Table.vue' // view-comfy-outline
import SortByOriginalOrderIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'
import SortByRankIcon from 'vue-material-design-icons/FormatListNumbered.vue'
import SortByDateOptionIcon from 'vue-material-design-icons/SortClockAscendingOutline.vue'

import { PollsAPI, ValidatorAPI } from '../../Api/index.ts'
import { RankedType, useOptionsStore } from '../../stores/options.ts'
import { usePollStore } from '../../stores/poll.ts'
import { usePreferencesStore } from '../../stores/preferences.ts'
import { useSessionStore } from '../../stores/session.ts'
import { useSubscriptionStore } from '../../stores/subscription.ts'
import { useVotesStore } from '../../stores/votes.ts'

import {
	StatusResults,
	ViewMode,
	PollType,
	ButtonMode,
	Event,
} from '../../Types/index.ts'

import { deleteCookieByValue, findCookieByValue } from '../../helpers/index.ts'
import { NcActionButtonGroup } from '@nextcloud/vue'
import ActionAddPoll from '../Actions/modules/ActionAddPoll.vue'
import { AxiosError } from '@nextcloud/axios'

type InputProps = {
	success: boolean
	error: boolean
	showTrailingButton: boolean
	labelOutside: boolean
	label: string
}

const optionsStore = useOptionsStore()
const pollStore = usePollStore()
const sessionStore = useSessionStore()
const subscriptionStore = useSubscriptionStore()
const preferencesStore = usePreferencesStore()
const votesStore = useVotesStore()
const router = useRouter()
const hasCookie = !!findCookieByValue(sessionStore.publicToken)
const viewMode = computed({
	get() {
		return pollStore.viewMode
	},
	set() {
		changeView()
	},
})

/**
 *
 */
function logout() {
	const reRouteTo = deleteCookieByValue(sessionStore.publicToken)
	if (reRouteTo) {
		router.push({
			name: 'publicVote',
			params: {
				token: reRouteTo,
			},
		})
	}
}

/**
 *
 */
async function writeSubscription() {
	subscriptionStore.write()
}

/**
 *
 */
async function deleteEmailAddress() {
	try {
		await sessionStore.deleteEmailAddress()
		showSuccess(t('polls', 'Email address deleted.'))
	} catch {
		showError(
			t('polls', 'Error deleting email address {emailAddress}', {
				emailAddress: sessionStore.share.user.emailAddress,
			}),
		)
	}
}

/**
 *
 */
async function resendInvitation() {
	try {
		const response = await sessionStore.resendInvitation()
		if (response) {
			showSuccess(
				t('polls', 'Invitation resent to {emailAddress}', {
					emailAddress: response.data.share.user.emailAddress,
				}),
			)
		}
	} catch {
		showError(
			t('polls', 'Mail could not be resent to {emailAddress}', {
				emailAddress: sessionStore.share.user.emailAddress,
			}),
		)
	}
}

/**
 *
 */
function changeView(): void {
	emit(Event.TransitionsOff, 500)
	if (pollStore.type === PollType.Date) {
		preferencesStore.setViewDatePoll(
			pollStore.viewMode === ViewMode.TableView
				? ViewMode.ListView
				: ViewMode.TableView,
		)
	} else if (pollStore.type === PollType.Text) {
		preferencesStore.setViewTextPoll(
			pollStore.viewMode === ViewMode.TableView
				? ViewMode.ListView
				: ViewMode.TableView,
		)
	}
}

/**
 *
 */
async function copyLink() {
	const personalLink =
		window.location.origin
		+ router.resolve({
			name: 'publicVote',
			params: { token: sessionStore.publicToken },
		}).href

	try {
		await navigator.clipboard.writeText(personalLink)
		showSuccess(t('polls', 'Link copied to clipboard'))
	} catch {
		showError(t('polls', 'Error while copying link to clipboard'))
	}
}

/**
 *
 */
async function getAddresses() {
	try {
		const response = await PollsAPI.getParticipantsEmailAddresses(
			sessionStore.route.params.id,
		)
		await navigator.clipboard.writeText(
			response.data.map((item) => item.combined).join(', '),
		)
		showSuccess(t('polls', 'Link copied to clipboard'))
	} catch (error) {
		if ((error as AxiosError)?.code === 'ERR_CANCELED') {
			return
		}
		showError(t('polls', 'Error while copying link to clipboard'))
	}
}

/**
 *
 */
async function resetVotes() {
	try {
		await votesStore.resetVotes()
		showSuccess(t('polls', 'Your votes are reset'))
	} catch {
		showError(t('polls', 'Error while resetting votes'))
	}
}

const displayNameInputProps = ref<InputProps>({
	success: false,
	error: false,
	showTrailingButton: true,
	labelOutside: false,
	label: t('polls', 'Change name'),
})

const validateDisplayName = debounce(async function () {
	if (sessionStore.share.user.displayName.length < 1) {
		setDisplayNameStatus(StatusResults.Error)
		return
	}

	if (
		sessionStore.share.user.displayName === sessionStore.currentUser.displayName
	) {
		setDisplayNameStatus(StatusResults.Unchanged)
		return
	}

	try {
		await ValidatorAPI.validateName(
			sessionStore.route.params.token,
			sessionStore.share.user.displayName,
		)
		setDisplayNameStatus(StatusResults.Success)
	} catch {
		setDisplayNameStatus(StatusResults.Error)
	}
}, 500)

/**
 *
 * @param status
 */
function setDisplayNameStatus(status: StatusResults) {
	displayNameInputProps.value.success = status === StatusResults.Success
	displayNameInputProps.value.error = status === StatusResults.Error
	displayNameInputProps.value.showTrailingButton = status === StatusResults.Success
}

/**
 *
 */
async function submitDisplayName() {
	try {
		await sessionStore.updateDisplayName({
			displayName: sessionStore.share.user.displayName,
		})
		showSuccess(t('polls', 'Name changed.'))
		setDisplayNameStatus(StatusResults.Unchanged)
	} catch {
		showError(t('polls', 'Error changing name.'))
		setDisplayNameStatus(StatusResults.Error)
	}
}

const eMailInputProps = ref<InputProps>({
	success: false,
	error: false,
	showTrailingButton: true,
	labelOutside: false,
	label: t('polls', 'Edit Email Address'),
})

const validateEMail = debounce(async function () {
	if (
		sessionStore.share.user.emailAddress
		=== sessionStore.currentUser.emailAddress
	) {
		setEMailStatus(StatusResults.Unchanged)
		return
	}

	try {
		await ValidatorAPI.validateEmailAddress(sessionStore.share.user.emailAddress)
		setEMailStatus(StatusResults.Success)
	} catch {
		setEMailStatus(StatusResults.Error)
	}
}, 500)

/**
 *
 * @param status
 */
function setEMailStatus(status: StatusResults) {
	eMailInputProps.value.success = status === StatusResults.Success
	eMailInputProps.value.error = status === StatusResults.Error
	eMailInputProps.value.showTrailingButton = status === StatusResults.Success
}

/**
 *
 */
async function submitEmail() {
	try {
		await sessionStore.updateEmailAddress({
			emailAddress: sessionStore.share.user.emailAddress,
		})
		showSuccess(
			t('polls', 'Email address {emailAddress} saved.', {
				emailAddress: sessionStore.share.user.emailAddress,
			}),
		)
		setEMailStatus(StatusResults.Unchanged)
	} catch {
		showError(
			t('polls', 'Error saving email address {emailAddress}', {
				emailAddress: sessionStore.share.user.emailAddress,
			}),
		)
		setEMailStatus(StatusResults.Error)
	}
}
</script>

<template>
	<NcActions primary>
		<template #icon>
			<SettingsIcon :size="20" decorative />
		</template>
		<NcActionButtonGroup name="View mode">
			<NcActionButton
				v-model="viewMode"
				:value="ViewMode.TableView"
				type="radio"
				:aria-label="t('polls', 'Switch to table view')">
				<template #icon>
					<TableViewIcon />
				</template>
			</NcActionButton>

			<NcActionButton
				v-model="viewMode"
				:value="ViewMode.ListView"
				type="radio"
				:aria-label="t('polls', 'Switch to list view')">
				<template #icon>
					<ListViewIcon />
				</template>
			</NcActionButton>
		</NcActionButtonGroup>

		<NcActionButtonGroup name="Options order">
			<NcActionButton
				v-model="optionsStore.ranked"
				:value="RankedType.notRanked"
				type="radio"
				:aria-label="
					pollStore.type === PollType.Date
						? t('polls', 'Switch to date order')
						: t('polls', 'Switch to original order')
				">
				<template #icon>
					<SortByDateOptionIcon v-if="pollStore.type === PollType.Date" />
					<SortByOriginalOrderIcon v-else />
				</template>
			</NcActionButton>

			<NcActionButton
				v-model="optionsStore.ranked"
				:value="RankedType.ranked"
				type="radio"
				:aria-label="t('polls', 'Switch to ranked order')">
				<template #icon>
					<SortByRankIcon />
				</template>
			</NcActionButton>
		</NcActionButtonGroup>

		<NcActionSeparator v-if="sessionStore.appPermissions.pollCreation" />

		<ActionAddPoll :button-mode="ButtonMode.ActionMenu" />

		<NcActionSeparator />

		<NcActionButton
			v-if="sessionStore.share?.type === 'external'"
			:name="t('polls', 'Copy your personal link to clipboard')"
			:aria-label="t('polls', 'Copy your personal link to clipboard')"
			@click="copyLink()">
			<template #icon>
				<ClippyIcon />
			</template>
		</NcActionButton>

		<NcActionSeparator v-if="sessionStore.share?.type === 'external'" />

		<NcActionInput
			v-if="sessionStore.share?.type === 'external'"
			v-bind="displayNameInputProps"
			v-model="sessionStore.share.user.displayName"
			@update:value-value="validateDisplayName"
			@submit="submitDisplayName">
			<template #icon>
				<EditAccountIcon />
			</template>
			{{ displayNameInputProps.label }}
		</NcActionInput>

		<NcActionInput
			v-if="sessionStore.share?.type === 'external'"
			v-bind="eMailInputProps"
			v-model="sessionStore.share.user.emailAddress"
			@update:model-value="validateEMail"
			@submit="submitEmail">
			<template #icon>
				<EditEmailIcon />
			</template>
			{{ eMailInputProps.label }}
		</NcActionInput>

		<NcActionButton
			v-if="sessionStore.share?.type === 'external'"
			:name="t('polls', 'Get your personal link per mail')"
			:aria-label="t('polls', 'Get your personal link per mail')"
			:disabled="!sessionStore.share.user.emailAddress"
			@click="resendInvitation()">
			<template #icon>
				<SendLinkPerEmailIcon />
			</template>
		</NcActionButton>

		<NcActionCheckbox
			:model-value="subscriptionStore.subscribed"
			:disabled="!pollStore.permissions.subscribe"
			title="check"
			@change="writeSubscription">
			{{ t('polls', 'Subscribe to notifications') }}
		</NcActionCheckbox>

		<NcActionButton
			v-if="
				sessionStore.share?.type === 'external'
				&& sessionStore.share.user.emailAddress
			"
			:name="t('polls', 'Remove Email Address')"
			:aria-label="t('polls', 'Remove Email Address')"
			@click="deleteEmailAddress">
			<template #icon>
				<DeleteIcon />
			</template>
		</NcActionButton>

		<NcActionButton
			v-if="pollStore.permissions.edit"
			:name="t('polls', 'Copy list of email addresses to clipboard')"
			:aria-label="t('polls', 'Copy list of email addresses to clipboard')"
			@click="getAddresses()">
			<template #icon>
				<ClippyIcon />
			</template>
		</NcActionButton>

		<NcActionButton
			v-if="pollStore.permissions.vote"
			:name="t('polls', 'Reset your votes')"
			:aria-label="t('polls', 'Reset your votes')"
			@click="resetVotes()">
			<template #icon>
				<ResetVotesIcon />
			</template>
		</NcActionButton>

		<NcActionButton
			v-if="sessionStore.share?.type === 'external' && hasCookie"
			:name="
				t('polls', 'Logout as {name} (delete cookie)', {
					name: sessionStore.currentUser.displayName,
				})
			"
			:aria-label="
				t('polls', 'Logout as {name} (delete cookie)', {
					name: sessionStore.currentUser.displayName,
				})
			"
			@click="logout()">
			<template #icon>
				<LogoutIcon />
			</template>
		</NcActionButton>
	</NcActions>
</template>
