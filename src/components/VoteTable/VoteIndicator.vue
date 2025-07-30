<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { ref,computed, watch } from 'vue'
import CheckIcon from 'vue-material-design-icons/Check.vue'
import CloseIcon from 'vue-material-design-icons/Close.vue'
import { MaybeIcon } from '../AppIcons/index.ts'
import { Answer } from '../../Types/index.ts'
import { usePollStore } from '../../stores/poll.ts'
const pollStore = usePollStore()
const chosenRank=JSON.parse(pollStore.configuration.chosenRank)


const ICON_SIZE = 26

interface Props {
	answer: Answer
	active?: boolean
}

const props = defineProps<Props>()

const selectedRank = ref(props.answer)

const emit = defineEmits(['click','selectChange'])

const colorCodeNo = getComputedStyle(document.documentElement).getPropertyValue(
	'--color-error',
)
const colorCodeYes = getComputedStyle(document.documentElement).getPropertyValue(
	'--color-success',
)
const colorCodeMaybe = getComputedStyle(document.documentElement).getPropertyValue(
	'--color-warning',
)

const foregroundColor = computed(() => {
	if (props.answer === 'yes') {
		return colorCodeYes
	}
	if (props.answer === 'maybe') {
		return colorCodeMaybe
	}
	return colorCodeNo
})

// Watchers
watch(() => props.answer, (newValue) => {
  selectedRank.value = newValue
})

// Methods
const handleRankSelected = (event) => {
  const rank = event.target.value
  emit('selectChange', rank)
}


function onClick() {
	if (props.active) {
		emit('click')
	}
}
</script>

<template>
	<div class="vote-cell-container">
	<div v-if="pollStore.votingVariant === 'generic'" class="generic-vote">
		<span v-if="!props.active" class="selected-value">
			{{ selectedRank }}
		</span>
		<select
			v-else
			:value="selectedRank"
			class="vote-ranking"
			@change="handleRankSelected">
			<option disabled value=""></option>
			<option v-for="rank in chosenRank" :key="rank" :value="rank">
				{{ rank }}
			</option>
		</select>
	</div>
	<div v-else :class="['vote-indicator', props.active]" @click="onClick()">
		<MaybeIcon v-if="props.answer === 'maybe'" :size="ICON_SIZE" />
		<CheckIcon
			v-if="answer === 'yes'"
			:fill-color="foregroundColor"
			:size="ICON_SIZE" />
		<CloseIcon
			v-if="answer === 'no'"
			:fill-color="foregroundColor"
			:size="ICON_SIZE" />
	</div>
	</div>
</template>

<style lang="scss">
.selected-value {
	background-color: #f9f9f9;
	border: 1px solid #ddd;
	color: #444;
	border-radius: 6px;
	padding: 4px 10px;
	display: inline-block;
	text-align: center;
	width: 100%;
}

.vote-cell-container {
  display: flex;
  justify-content: center;
}

.generic-vote {
  width: fit-content;
  min-width: 60px;
  max-width: 200px;
  display: flex;
  justify-content: center;
  align-items: center;
  height:100%;
}  
.vote-ranking {
    width: auto;
    padding: 6px 24px 6px 12px;
    
    /* dynamic size based on content size */
    &::after {
      content: attr(data-content);
      visibility: hidden;
      padding: inherit;
      font: inherit;
    }
  }

.vote-indicator {
	color: var(--color-polls-foreground-no);
	min-width: 30px;
	min-height: 30px;

	&,
	* {
		transition: all 0.4s ease-in-out;
		margin: auto;
		.active & {
			cursor: pointer;
		}
	}

	.active & {
		border: 2px solid;
		border-radius: var(--border-radius);
		.material-design-icon {
			width: 26px;
			height: 26px;
		}
	}
	.yes & {
		color: var(--color-polls-foreground-yes);
	}

	.maybe & {
		color: var(--color-polls-foreground-maybe);
	}

	.active:hover & {
		width: 35px;
		height: 35px;
		.material-design-icon {
			width: 31px;
			height: 31px;
		}
	}
}

.error-message {
  color: var(--color-error);
}
</style>
