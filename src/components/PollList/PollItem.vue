<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { computed } from 'vue'
import { DateTime } from 'luxon'
import { t } from '@nextcloud/l10n'
import NcUserBubble from '@nextcloud/vue/components/NcUserBubble'

import {
	usePollStore,
	AccessType,
	Poll,
	PollType,
	pollTypes,
} from '../../stores/poll'
import { usePreferencesStore } from '../../stores/preferences.ts'
import BadgeSmallDiv from '../Base/modules/BadgeSmallDiv.vue'
import { StatusResults } from '../../Types/index.ts'

// Icons
import TextPollIcon from 'vue-material-design-icons/FormatListBulletedSquare.vue'
import DatePollIcon from 'vue-material-design-icons/CalendarBlank.vue'
import ExpirationIcon from 'vue-material-design-icons/CalendarEnd.vue'
import PrivatePollIcon from 'vue-material-design-icons/Key.vue'
import OpenPollIcon from 'vue-material-design-icons/Earth.vue'
import ArchivedPollIcon from 'vue-material-design-icons/Archive.vue'
import ClosedPollsIcon from 'vue-material-design-icons/Lock.vue'
import LockPollIcon from 'vue-material-design-icons/Security.vue'
import ParticipantsIcon from 'vue-material-design-icons/AccountMultipleCheck.vue'
import ParticipatedIcon from 'vue-material-design-icons/AccountCheck.vue'
import AdminIcon from 'vue-material-design-icons/ShieldCrown.vue'

interface Props {
	poll: Poll
	noLink?: boolean
}

const { poll, noLink = false } = defineProps<Props>()

const pollStore = usePollStore()
const preferencesStore = usePreferencesStore()
const closeToClosing = computed(
	() =>
		!poll.status.isExpired
		&& poll.configuration.expire
		&& DateTime.fromMillis(poll.configuration.expire * 1000).diffNow('hours')
			.hours < 36,
)

const timeExpirationRelative = computed(() => {
	if (poll.configuration.expire) {
		return DateTime.fromMillis(poll.configuration.expire * 1000).toRelative()
	}
	return t('polls', 'never')
})

const expiryClass = computed(() => {
	if (poll.status.isExpired) {
		return StatusResults.Error
	}

	if (poll.configuration.expire && closeToClosing.value) {
		return StatusResults.Warning
	}

	if (poll.configuration.expire && !poll.status.isExpired) {
		return StatusResults.Success
	}

	return StatusResults.Success
})

const timeCreatedRelative = computed(
	() => DateTime.fromMillis(poll.status.created * 1000).toRelative() as string,
)

const descriptionLine = computed(() => {
	if (preferencesStore.user.verbosePollsList) {
		if (poll.configuration.description) {
			return poll.configuration.description
		}
		return t('polls', 'No description provided')
	}

	if (poll.status.isArchived) {
		return t('polls', 'Archived {relativeTime}', {
			relativeTime: DateTime.fromMillis(
				poll.status.archivedDate * 1000,
			).toRelative() as string,
		})
	}

	return t('polls', 'Started {relativeTime} from {ownerName}', {
		ownerName: poll.owner.displayName,
		relativeTime: timeCreatedRelative.value,
	})
})
</script>

<template>
	<div class="poll-item">
		<TextPollIcon
			v-if="poll.type === PollType.Text"
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
				<LockPollIcon :size="16" />
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
				<ArchivedPollIcon
					v-if="
						!preferencesStore.user.verbosePollsList
						&& poll.status.isArchived
					"
					:title="t('polls', 'Archived  poll')"
					:size="16" />
				<OpenPollIcon
					v-else-if="
						!preferencesStore.user.verbosePollsList
						&& poll.configuration.access === AccessType.Open
					"
					:title="t('polls', 'Openly accessible poll')"
					:size="16" />
				<PrivatePollIcon
					v-else-if="
						!preferencesStore.user.verbosePollsList
						&& poll.configuration.access === AccessType.Private
					"
					:title="t('polls', 'Private poll')"
					:size="16" />

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
					&& poll.configuration.access === AccessType.Private
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
					&& poll.configuration.access === AccessType.Open
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
				:title="t('polls', 'You have delegated admin rights')">
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
						style="color: var(--color-success-text)" />
				</template>
			</BadgeSmallDiv>

			<NcUserBubble
				v-if="preferencesStore.user.verbosePollsList"
				:user="poll.owner.id"
				:display-name="poll.owner.displayName"
				:show-user-status="false"
				:title="
					t('polls', 'Poll owner: {ownerName}', {
						ownerName: poll.owner.displayName,
					})
				" />

			<BadgeSmallDiv
				v-if="poll.configuration.expire"
				:class="expiryClass"
				:title="t('polls', 'Expiration')">
				>
				<template #icon>
					<ClosedPollsIcon v-if="poll.status.isExpired" :size="16" />
					<ExpirationIcon v-else :size="16" />
				</template>
				{{ timeExpirationRelative }}
			</BadgeSmallDiv>
		</div>

		<slot name="actions" />
	</div>
</template>

<style lang="scss">
.poll-item {
	display: flex;
	column-gap: 0.3rem;
	align-items: center;
	padding: 0.3rem 0;
	border-bottom: 1px solid var(--color-border-dark);

	&.active {
		background-color: var(--color-primary-element-light);
	}

	&:hover {
		background-color: var(--color-background-hover);
	}

	.item__type {
		flex: 0 0 3rem;
	}

	.item__title {
		flex: 4 0 11.3rem;
		overflow: hidden;
	}

	.title_line,
	.description_line {
		display: flex;
		gap: 0.5rem;
		.title,
		.description {
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;
		}
		.title {
			flex: 1;
			font-weight: 600;
		}
	}

	.title-line {
		justify-content: space-between;
	}
	.description_line {
		opacity: 0.5;
	}
	.badges {
		display: flex;
		flex: 1 0 10rem;
		gap: 0.25rem;
		flex-wrap: wrap;
		justify-content: flex-end;
		align-items: center;

		.user-bubble__wrapper {
			line-height: normal;
			min-height: 1.4rem;
		}
	}
	.action-item {
		display: flex;
		flex: 0 0 2.7rem;
		justify-content: center;
	}
}
</style>
