<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import OptionsDateAdd from './OptionsDateAdd.vue'
import OptionsTextAdd from './OptionsTextAdd.vue'
import { t } from '@nextcloud/l10n'
import { usePollStore, PollType } from '../../stores/poll.ts'

const pollStore = usePollStore()
</script>

<template>
	<div class="option-proposals">
		<!-- <div>{{ proposalsStatus }}</div> -->
		<div v-if="pollStore.isProposalOpen" class="option-proposals__add-proposal">
			<OptionsDateAdd
				v-if="pollStore.type === PollType.Date"
				:caption="t('polls', 'Propose a date')"
				class="add-date-proposal"
				show-caption
				primary />
			<OptionsTextAdd
				v-if="pollStore.type === PollType.Text"
				:placeholder="t('polls', 'Propose an option')"
				class="add-text-proposal" />
		</div>
	</div>
</template>

<style lang="scss">
.option-proposals {
	align-self: center;
	display: flex;
	flex-direction: column;
}

.option-proposals__add-proposal {
	padding: 8px 0;
	.add-date-proposal {
		min-width: 85px;
	}
}
</style>
