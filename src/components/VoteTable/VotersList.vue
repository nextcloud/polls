<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'

import { useVotesStore } from '../../stores/votes'

import type { Option } from '../../stores/options.types'
import UserItem from '../User/UserItem.vue'
import { t } from '@nextcloud/l10n'

interface Props {
	option: Option
	answerFilter: 'yes' | 'maybe' | 'no' | '' | null
}

const { option, answerFilter = null } = defineProps<Props>()

const votesStore = useVotesStore()

const voters = computed(() =>
	votesStore.getVotersByOptionAndAnswer({
		optionText: option.text,
		answer: answerFilter,
	}),
)
</script>

<template>
	<div v-if="voters.length === 0" class="no-voters">
		<strong>{{ t('polls', 'No voters') }}</strong>
	</div>
	<div v-else class="voters-grid">
		<UserItem
			v-for="(voter, index) in voters"
			:key="voter.id"
			condensed
			:user="voter"
			:item-style="{ '--i': index }" />
	</div>
	<strong v-if="voters.length > 4" class="more-voters">
		+{{ voters.length - 4 }} {{ t('polls', 'more') }}
	</strong>
</template>

<style lang="scss">
.voters-grid {
	display: grid;
	grid-template-columns: repeat(4, auto);
	gap: 0.5rem 1rem;

	.user-item {
		grid-column: 1/2;
		grid-row: 1/2;
		--step: 16px;
		translate: calc(var(--i, 0) * var(--step)) 0;

		&:nth-child(n + 5) {
			display: none;
		}

		.user-item__name {
			display: none;
		}
	}

	&:hover {
		.user-item {
			grid-column: auto;
			grid-row: auto;
			--step: 0;

			&:nth-child(n + 5) {
				display: flex;
			}

			.user-item__name {
				display: initial;
			}
		}

		.more-voters {
			display: none;
		}
	}
}

.no-voters,
.more-voters {
	text-align: center;
	padding: 1rem;
}
</style>
