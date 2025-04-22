<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import moment from '@nextcloud/moment'
import { t } from '@nextcloud/l10n'

import { usePollStore, AccessType } from '../../stores/poll.ts'
import { useSharesStore } from '../../stores/shares.ts'

import unpublishedIcon from 'vue-material-design-icons/PublishOff.vue'
import archivedPollIcon from 'vue-material-design-icons/Archive.vue'
import closedPollIcon from 'vue-material-design-icons/Lock.vue'
import creationIcon from 'vue-material-design-icons/ClockOutline.vue'
import ProposalsIcon from 'vue-material-design-icons/Offer.vue'
import ExpirationIcon from 'vue-material-design-icons/CalendarEnd.vue'

const pollStore = usePollStore()
const sharesStore = useSharesStore()

const isNoAccessSet = computed(
	() =>
		pollStore.configuration.access === AccessType.Private
		&& !sharesStore.hasShares
		&& pollStore.permissions.edit,
)

const subTexts = computed(() => {
	const subTexts = []

	if (pollStore.status.isArchived) {
		subTexts.push({
			id: 'deleted',
			text: t('polls', 'Archived'),
			class: 'archived',
			iconComponent: archivedPollIcon,
		})
		return subTexts
	}

	if (isNoAccessSet.value) {
		subTexts.push({
			id: 'no-access',
			text: [t('polls', 'Unpublished')].join('. '),
			class: 'unpublished',
			iconComponent: unpublishedIcon,
		})
		return subTexts
	}

	if (pollStore.configuration.access === AccessType.Private) {
		subTexts.push({
			id: pollStore.configuration.access,
			text: t('polls', 'A private poll from {name}', {
				name: pollStore.owner.displayName,
			}),
			class: '',
			iconComponent: null,
		})
	} else {
		subTexts.push({
			id: pollStore.configuration.access,
			text: t('polls', 'An openly accessible poll from {name}', {
				name: pollStore.owner.displayName,
			}),
			class: '',
			iconComponent: null,
		})
	}

	if (pollStore.isClosed) {
		subTexts.push({
			id: 'closed',
			text: timeExpirationRelative.value,
			class: 'closed',
			iconComponent: closedPollIcon,
		})
		return subTexts
	}

	if (!pollStore.isClosed && pollStore.configuration.expire) {
		subTexts.push({
			id: 'expiring',
			text: t('polls', 'Closing {relativeExpirationTime}', {
				relativeExpirationTime: timeExpirationRelative.value,
			}),
			class: closeToClosing.value ? 'closing' : 'open',
			iconComponent: ExpirationIcon,
		})
		return subTexts
	}

	if (pollStore.isProposalExpirySet && pollStore.isProposalExpired) {
		subTexts.push({
			id: 'expired',
			text: t('polls', 'Proposal period ended {timeRelative}', {
				timeRelative: pollStore.proposalsExpireRelative,
			}),
			class: 'proposal',
			iconComponent: ProposalsIcon,
		})
		return subTexts
	}

	if (pollStore.isProposalExpirySet && !pollStore.isProposalExpired) {
		subTexts.push({
			id: 'proposal-open',
			text: t('polls', 'Proposal period ends {timeRelative}', {
				timeRelative: pollStore.proposalsExpireRelative,
			}),
			class: 'proposal',
			iconComponent: ProposalsIcon,
		})
		return subTexts
	}

	if (subTexts.length < 2) {
		subTexts.push({
			id: 'created',
			text: dateCreatedRelative.value,
			class: 'created',
			iconComponent: creationIcon,
		})
	}
	return subTexts
})

const dateCreatedRelative = computed(() =>
	moment.unix(pollStore.status.created).fromNow(),
)

const closeToClosing = computed(
	() =>
		!pollStore.isClosed
		&& pollStore.configuration.expire
		&& moment.unix(pollStore.configuration.expire).diff() < 86400000,
)

const timeExpirationRelative = computed(() => {
	if (pollStore.configuration.expire) {
		return moment.unix(pollStore.configuration.expire).fromNow()
	}
	return t('polls', 'never')
})
</script>

<template>
	<div class="poll-info-line">
		<span
			v-for="subText in subTexts"
			:key="subText.id"
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
			color: var(--color-error);
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
