<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import { t } from '@nextcloud/l10n'

import { usePollStore } from '../../stores/poll'
import { useSharesStore } from '../../stores/shares'

import UnpublishedIcon from 'vue-material-design-icons/PublishOff.vue'
import ArchivedPollIcon from 'vue-material-design-icons/ArchiveOutline.vue'
import ClosedPollIcon from 'vue-material-design-icons/LockOutline.vue'
import CreationIcon from 'vue-material-design-icons/ClockOutline.vue'
import TimezoneIcon from 'vue-material-design-icons/MapClockOutline.vue'
import ProposalsIcon from 'vue-material-design-icons/HandExtendedOutline.vue'
import ExpirationIcon from 'vue-material-design-icons/CalendarEndOutline.vue'
import { DateTime } from 'luxon'
import { useSessionStore } from '@/stores/session'

const pollStore = usePollStore()
const sharesStore = useSharesStore()
const sessionStore = useSessionStore()

const isNoAccessSet = computed(
	() =>
		pollStore.configuration.access === 'private'
		&& !sharesStore.hasShares
		&& pollStore.permissions.edit,
)

const subTexts = computed(() => {
	const subTexts = []

	subTexts.push({
		id: 'timezone',
		text: sessionStore.currentTimezoneName,
		class: 'timeZone',
		iconComponent: TimezoneIcon,
		title: '',
	})

	if (pollStore.status.isArchived) {
		subTexts.push({
			id: 'deleted',
			text: t('polls', 'Archived'),
			class: 'archived',
			iconComponent: ArchivedPollIcon,
			title: '',
		})
		return subTexts
	}

	if (isNoAccessSet.value) {
		subTexts.push({
			id: 'no-access',
			text: [t('polls', 'Unpublished')].join('. '),
			class: 'unpublished',
			iconComponent: UnpublishedIcon,
			title: '',
		})
		return subTexts
	}

	if (pollStore.configuration.access === 'private') {
		subTexts.push({
			id: pollStore.configuration.access,
			text: t('polls', 'A private poll from {name}', {
				name: pollStore.owner.displayName,
			}),
			class: '',
			iconComponent: null,
			title: '',
		})
	} else {
		subTexts.push({
			id: pollStore.configuration.access,
			text: t('polls', 'An openly accessible poll from {name}', {
				name: pollStore.owner.displayName,
			}),
			class: '',
			iconComponent: null,
			title: '',
		})
	}

	if (pollStore.isClosed) {
		subTexts.push({
			id: 'closed',
			text: pollStore.getExpirationDateTime.toRelative() as string,
			class: 'closed',
			iconComponent: ClosedPollIcon,
			title: pollStore.getExpirationDateTime.toLocaleString(
				DateTime.DATETIME_SHORT,
			) as string,
		})
		return subTexts
	}

	if (!pollStore.isClosed && pollStore.configuration.expire) {
		subTexts.push({
			id: 'expiring',
			text: t('polls', 'Closing {relativeExpirationTime}', {
				relativeExpirationTime:
					pollStore.getExpirationDateTime.toRelative() as string,
			}),
			class: closeToClosing.value ? 'closing' : 'open',
			iconComponent: ExpirationIcon,
			title: pollStore.getExpirationDateTime.toLocaleString(
				DateTime.DATETIME_SHORT,
			) as string,
		})
		return subTexts
	}

	if (pollStore.isProposalExpirySet && pollStore.isProposalExpired) {
		subTexts.push({
			id: 'expired',
			text: t('polls', 'Proposal period ended {timeRelative}', {
				timeRelative:
					pollStore.getProposalExpirationDateTime.toRelative() as string,
			}),
			class: 'proposal',
			iconComponent: ProposalsIcon,
			title: pollStore.getProposalExpirationDateTime.toLocaleString(
				DateTime.DATETIME_SHORT,
			) as string,
		})
		return subTexts
	}

	if (pollStore.isProposalExpirySet && !pollStore.isProposalExpired) {
		subTexts.push({
			id: 'proposal-open',
			text: t('polls', 'Proposal period ends {timeRelative}', {
				timeRelative:
					pollStore.getProposalExpirationDateTime.toRelative() as string,
			}),
			class: 'proposal',
			iconComponent: ProposalsIcon,
			title: pollStore.getProposalExpirationDateTime.toLocaleString(
				DateTime.DATETIME_SHORT,
			) as string,
		})
		return subTexts
	}

	if (subTexts.length < 2) {
		subTexts.push({
			id: 'created',
			text: pollStore.getCreationDateTime.toRelative(),
			class: 'created',
			iconComponent: CreationIcon,
			title: pollStore.getCreationDateTime.toLocaleString(
				DateTime.DATETIME_SHORT,
			) as string,
		})
	}

	return subTexts
})

const closeToClosing = computed(
	() =>
		!pollStore.isClosed
		&& pollStore.configuration.expire
		&& pollStore.getExpirationDateTime.diffNow().as('days') < 1,
)
</script>

<template>
	<div class="poll-info-line">
		<span
			v-for="subText in subTexts"
			:key="subText.id"
			:title="subText.title"
			:class="['sub-text', subText.class]">
			<Component :is="subText.iconComponent" :size="16" />
			<span class="sub-text">{{ subText.text }}</span>
		</span>
	</div>
</template>

<style lang="scss">
.poll-info-line {
	display: flex;
	flex-wrap: wrap;
	opacity: 0.7;
	font-size: 1em;

	.material-design-icon {
		padding: 0 2px;
	}

	.sub-text {
		display: flex;
	}

	& > span:not(:last-child)::after {
		content: '|';
		padding: 0 2px;
	}

	.closed,
	.archived {
		.sub-text {
			color: var(--color-error-text);
			font-weight: 700;
		}
	}

	.unpublished,
	.open {
		.sub-text {
			font-weight: 700;
		}
	}

	.closing {
		.sub-text {
			color: var(--color-warning);
			font-weight: 700;
		}
	}

	.created {
		.sub-text {
			color: var(--color-main-text);
		}
	}
}
</style>
