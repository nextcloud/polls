<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'

import { usePollStore } from '../../stores/poll'
import { useOptionsStore } from '../../stores/options'
import { useVotesStore } from '../../stores/votes'
import Counter from '../Options/Counter.vue'

const pollStore = usePollStore()
const optionsStore = useOptionsStore()
const votesStore = useVotesStore()

const tableStyle = computed(() => ({
	'--participants-count': `${votesStore.chunkedParticipants.length}`,
	'--options-count': `${optionsStore.options.length}`,
}))
</script>

<template>
	<TransitionGroup
		id="vote-table-text"
		tag="div"
		name="list"
		:class="pollStore.viewMode"
		class="vote-table-text"
		:style="tableStyle">
		<ul v-for="option in optionsStore.orderedOptions" :key="option.id">
			<li class="option">
				<div class="option__title">
					{{ option.text }}
				</div>
				<div v-if="option.description" class="option__description">
					{{ option.description }}
				</div>
				<Counter
					v-if="pollStore.permissions.seeResults"
					:id="`counter-${option.id}`"
					:key="`counter-${option.id}`"
					:class="{
						confirmed: option.confirmed && pollStore.status.isExpired,
					}"
					:show-maybe="pollStore.configuration.allowMaybe"
					:option="option" />
			</li>
		</ul>
	</TransitionGroup>
</template>

<style lang="scss">
li.option {
	display: flex;
	flex-direction: column;
	padding: 0.5em;
	border-bottom: 1px solid var(--color-polls-border);
	&__title {
		font-weight: 500;
	}
	&__description {
		font-size: 0.9em;
		color: var(--color-polls-foreground-secondary);
	}
}
</style>
