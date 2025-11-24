<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { DateTime } from 'luxon'
import { t } from '@nextcloud/l10n'

// Icons
import TextPollIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'
import DatePollIcon from 'vue-material-design-icons/CalendarBlankOutline.vue'
import ExpirationIcon from 'vue-material-design-icons/CalendarEndOutline.vue'
import PrivatePollIcon from 'vue-material-design-icons/Key.vue'
import OpenPollIcon from 'vue-material-design-icons/Earth.vue'
import ArchivedPollIcon from 'vue-material-design-icons/ArchiveOutline.vue'
import ClosedPollsIcon from 'vue-material-design-icons/LockOutline.vue'
import LockPollIcon from 'vue-material-design-icons/Security.vue'
import ParticipantsIcon from 'vue-material-design-icons/AccountMultipleCheckOutline.vue'
import ParticipatedIcon from 'vue-material-design-icons/AccountCheckOutline.vue'
import AdminIcon from 'vue-material-design-icons/ShieldCrownOutline.vue'
import UserBubble from '../User/UserBubble.vue'

import BadgeSmallDiv from '../Base/modules/BadgeSmallDiv.vue'

import { usePollStore, pollTypes } from '../../stores/poll'
import { usePreferencesStore } from '../../stores/preferences'

import type { Poll } from '../../stores/poll.types'

interface Props {
	poll: Poll
	noLink?: boolean
}

const { poll, noLink = false } = defineProps<Props>()

const pollStore = usePollStore()
const preferencesStore = usePreferencesStore()

const expirationDateTime = computed(() =>
	DateTime.fromMillis(poll.configuration.expire * 1000),
)

const archiveDateTime = computed(() =>
	DateTime.fromMillis(poll.status.archivedDate * 1000),
)

const closeToClosing = computed(
	() =>
		!poll.status.isExpired
		&& poll.configuration.expire
		&& expirationDateTime.value.diffNow('hours').hours < 36,
)

const expiryClass = computed(() => {
	if (poll.status.isExpired) {
		return 'error'
	}

	if (poll.configuration.expire && closeToClosing.value) {
		return 'warning'
	}

	if (poll.configuration.expire && !poll.status.isExpired) {
		return 'success'
	}

	return 'success'
})

const pollDetails = computed(() => {
	if (noLink || !poll.permissions.view) {
		return {
			iconComponent: LockPollIcon,
			title: t('polls', 'No access'),
			description: t('polls', 'No access to this poll of {ownerName}.', {
				ownerName: poll.owner.displayName,
			}),
		}
	}
	if (poll.status.isArchived) {
		return {
			iconComponent: ArchivedPollIcon,
			title: t('polls', 'Archived poll'),
			description: t('polls', 'Archived {relativeTime}', {
				relativeTime: archiveDateTime.value.toRelative() as string,
			}),
		}
	}
	if (poll.configuration.access === 'private') {
		return {
			iconComponent: PrivatePollIcon,
			title: t('polls', 'Private poll'),
			description: t(
				'polls',
				'Private poll, only invited participants have access',
			),
		}
	}
	return {
		iconComponent: OpenPollIcon,
		title: t('polls', 'Openly accessible poll'),
		description: t(
			'polls',
			'Open poll, accessible to all users of this instance and invited participants',
		),
	}
})

const descriptionLine = computed(
	() => poll.configuration.description || pollDetails.value.description,
)
</script>

<template>
	<div class="poll-item">
		<TextPollIcon
			v-if="poll.type === 'textPoll'"
			class="item__type"
			:title="pollTypes[poll.type].name" />
		<DatePollIcon v-else class="item__type" :title="pollTypes[poll.type].name" />

		<div
			v-if="noLink || !poll.permissions.view"
			class="item__title"
			:class="{ closed: poll.status.isExpired }">
			<div class="title">
				{{ poll.configuration.title }}
			</div>

			<div class="description_line">
				<component :is="pollDetails.iconComponent" :size="16" />
				<div class="description">
					{{
						t('polls', 'No access to this poll of {ownerName}.', {
							ownerName: poll.owner.displayName,
						})
					}}
				</div>
			</div>
		</div>

		<RouterLink
			v-else
			class="item__title"
			:title="poll.configuration.description"
			:to="{
				name: 'vote',
				params: { id: poll.id },
			}"
			:class="{
				closed: poll.status.isExpired,
				active: poll.id === pollStore.id,
			}">
			<div class="title_line">
				<span class="title">
					{{ poll.configuration.title }}
				</span>
			</div>

			<div class="description_line">
				<component
					:is="pollDetails.iconComponent"
					:size="16"
					:title="pollDetails.title" />

				<span class="description">
					{{ descriptionLine }}
				</span>
			</div>
		</RouterLink>

		<div class="badges">
			<BadgeSmallDiv
				v-if="preferencesStore.user.verbosePollsList"
				:title="
					t('polls', '{count} participants', {
						count: poll.status.countParticipants,
					})
				">
				<template #icon> <ParticipantsIcon :size="16" /> </template>
				{{ poll.status.countParticipants }}
			</BadgeSmallDiv>

			<BadgeSmallDiv
				v-if="
					preferencesStore.user.verbosePollsList
					&& !poll.status.isArchived
					&& poll.configuration.access === 'private'
				"
				:title="
					t('polls', 'Private poll, only invited participants have access')
				">
				<template #icon>
					<PrivatePollIcon :size="16" />
				</template>
			</BadgeSmallDiv>

			<BadgeSmallDiv
				v-if="
					preferencesStore.user.verbosePollsList
					&& !poll.status.isArchived
					&& poll.configuration.access === 'open'
				"
				:title="
					t('polls', 'Open poll, accessible to all users of this instance')
				">
				<template #icon>
					<OpenPollIcon :size="16" />
				</template>
			</BadgeSmallDiv>

			<BadgeSmallDiv
				v-if="
					preferencesStore.user.verbosePollsList && poll.status.isArchived
				"
				:title="t('polls', 'Archived poll')">
				<template #icon>
					<ArchivedPollIcon :size="16" />
				</template>
				{{ t('polls', 'Archived poll') }}
			</BadgeSmallDiv>

			<BadgeSmallDiv
				v-if="
					preferencesStore.user.verbosePollsList
					&& poll.currentUserStatus.userRole === 'admin'
				"
				:title="t('polls', 'You have been granted administrative rights')">
				<template #icon>
					<AdminIcon :size="16" />
				</template>
			</BadgeSmallDiv>

			<BadgeSmallDiv
				v-if="
					preferencesStore.user.verbosePollsList
					&& poll.currentUserStatus.countVotes
				"
				:title="t('polls', 'You participated')">
				<template #icon>
					<ParticipatedIcon
						:size="16"
						style="color: var(--color-element-success)" />
				</template>
			</BadgeSmallDiv>

			<UserBubble
				v-if="preferencesStore.user.verbosePollsList"
				:user="poll.owner"
				:title="
					t('polls', 'Poll owner: {ownerName}', {
						ownerName: poll.owner.displayName,
					})
				" />

			<BadgeSmallDiv
				v-if="poll.configuration.expire"
				:class="expiryClass"
				:title="
					t(
						'polls',
						poll.status.isExpired
							? 'Expired {dateTime}'
							: 'Expires {dateTime}',
						{
							dateTime: expirationDateTime.toLocaleString(
								DateTime.DATETIME_SHORT,
							) as string,
						},
					)
				">
				>
				<template #icon>
					<ClosedPollsIcon v-if="poll.status.isExpired" :size="16" />
					<ExpirationIcon v-else :size="16" />
				</template>
				{{
					expirationDateTime
						? expirationDateTime.toRelative()
						: t('polls', 'never')
				}}
			</BadgeSmallDiv>
		</div>

		<div class="actions">
			<slot name="actions" />
		</div>
	</div>
</template>

<style lang="scss">
.poll-item {
	display: grid;
	grid-template-columns: subgrid;
	column-gap: 1rem;
	align-items: center;
	padding: 0.3rem 0;
	border-bottom: 1px solid var(--color-border-dark);
	grid-column: 1 / 5;

	&.active {
		background-color: var(--color-primary-element-light);
	}

	&:hover {
		background-color: var(--color-background-hover);
	}

	.item__title * {
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}

	.title {
		font-weight: 600;
	}

	.title-line {
		justify-content: space-between;
	}
	.description_line {
		display: grid;
		grid-template-columns: max-content 1fr;
		gap: 0.25rem;
		opacity: 0.5;
	}
	.badges {
		display: flex;
		gap: 0.25rem;
		flex-wrap: wrap;
		justify-content: flex-end;
		align-items: center;

		.user-bubble__wrapper {
			line-height: normal;
			min-height: 1.4rem;
		}
	}
}
</style>
