<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { computed } from 'vue'
import moment from '@nextcloud/moment'
import { t } from '@nextcloud/l10n'
import {
	usePollStore,
	AccessType,
	Poll,
	PollType,
	pollTypes,
} from '../../stores/poll'
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

interface Props {
	poll: Poll
	noLink?: boolean
}

const { poll, noLink = false } = defineProps<Props>()

const pollStore = usePollStore()

const closeToClosing = computed(
	() =>
		!poll.status.isExpired
		&& poll.configuration.expire
		&& moment.unix(poll.configuration.expire).diff() < 86400000,
)

const timeExpirationRelative = computed(() => {
	if (poll.configuration.expire) {
		return moment.unix(poll.configuration.expire).fromNow()
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

const timeCreatedRelative = computed(() =>
	moment.unix(poll.status.created).fromNow(),
)

const descriptionLine = computed(() => {
	if (poll.status.isArchived) {
		return t('polls', 'Archived {relativeTIme}', {
			relativeTIme: moment.unix(poll.status.archivedDate).fromNow(),
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
				<BadgeSmallDiv v-if="poll.configuration.expire" :class="expiryClass">
					<template #icon>
						<ClosedPollsIcon v-if="poll.status.isExpired" :size="16" />
						<ExpirationIcon v-else :size="16" />
					</template>
					{{ timeExpirationRelative }}
				</BadgeSmallDiv>
			</div>

			<div class="description_line">
				<ArchivedPollIcon
					v-if="poll.status.isArchived"
					:title="t('polls', 'Archived  poll')"
					:size="16" />
				<OpenPollIcon
					v-else-if="poll.configuration.access === AccessType.Open"
					:title="t('polls', 'Openly accessible poll')"
					:size="16" />
				<PrivatePollIcon
					v-else
					:title="t('polls', 'Private poll')"
					:size="16" />

				<span class="description">
					{{ descriptionLine }}
				</span>
			</div>
		</RouterLink>

		<slot name="actions" />
	</div>
</template>

<style lang="scss">
.poll-item {
	display: flex;
	column-gap: 4px;
	align-items: center;
	padding: 4px 0;
	border-bottom: 1px solid var(--color-border-dark);

	&.active {
		background-color: var(--color-primary-element-light);
	}

	&:hover {
		background-color: var(--color-background-hover);
	}

	.item__type {
		flex: 0 0 44px;
	}

	.item__title {
		flex: 1 0 170px;
		overflow: hidden;
	}

	.title_line,
	.description_line {
		display: flex;
		gap: 8px;
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

	.action-item {
		display: flex;
		flex: 0 0 40px;
		justify-content: center;
	}
}
</style>
