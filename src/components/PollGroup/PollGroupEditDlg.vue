<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { t } from '@nextcloud/l10n'

import NcButton from '@nextcloud/vue/components/NcButton'

import SpeakerIcon from 'vue-material-design-icons/Bullhorn.vue'
import SpeakerBigIcon from 'vue-material-design-icons/BullhornVariant.vue'
import DescriptionIcon from 'vue-material-design-icons/TextBox.vue'

import { ConfigBox, InputDiv } from '../Base/index.ts'

import { usePollsStore } from '../../stores/polls.ts'
import { showError } from '@nextcloud/dialogs'
import { useRoute } from 'vue-router'
import { router } from '../../router.ts'

const pollsStore = usePollsStore()
const route = useRoute()
const helperTexts = {
	title: t('polls', 'Choose a brief title for the navigation bar and the slug'),
	titleExt: t('polls', 'Choose a more meaningful title for the overview page'),
	description: t('polls', 'Choose a description for the overview page'),
	titleChangedNote: t('polls', 'Note: Changing the title, also changes the URL'),
}
const emit = defineEmits<{
	(e: 'close'): void
	(e: 'updated'): void
}>()

const newGroupAttributes = ref({
	slug: route.params.slug as string,
	title: pollsStore.currentGroup?.title || '',
	titleExt: pollsStore.currentGroup?.titleExt || '',
	description: pollsStore.currentGroup?.description || '',
})

watch(route, (route) => {
	resetInputs(route.params.slug as string)
})

const pollGroupTitle = computed({
	get() {
		return pollsStore.currentGroup?.title || ''
	},
	set(newTitle: string) {
		newGroupAttributes.value.title = newTitle
	},
})
const pollGroupTitleExt = computed({
	get() {
		return pollsStore.currentGroup?.titleExt || ''
	},
	set(newTitleExt: string) {
		newGroupAttributes.value.titleExt = newTitleExt
	},
})

const pollGroupTitleDescription = computed({
	get() {
		return pollsStore.currentGroup?.description || ''
	},
	set(newDescription: string) {
		newGroupAttributes.value.description = newDescription
	},
})

const updating = ref(false)

const titleUpdated = computed(
	() => pollGroupTitle.value !== pollsStore.currentGroup?.title,
)
const titleIsEmpty = computed(() => pollGroupTitle.value === '')
const disableEditButton = computed(() => titleIsEmpty.value || updating.value)

function resetInputs(slug: string) {
	newGroupAttributes.value = {
		slug,
		title: pollsStore.currentGroup?.title || '',
		titleExt: pollsStore.currentGroup?.titleExt || '',
		description: pollsStore.currentGroup?.description || '',
	}
}

async function updatePollGroup() {
	try {
		// block the modal to prevent double submission
		updating.value = true
		// add the poll
		const pollGroup = await pollsStore.updatePollGroup(newGroupAttributes.value)

		if (pollGroup) {
			resetInputs(route.params.slug as string)
			emit('updated')
			router.push({
				name: 'group',
				params: { slug: pollGroup.slug },
			})
		}
	} catch {
		showError(t('polls', 'Error updating PollGroup'))
	} finally {
		// unblock the modal
		updating.value = false
	}
}
</script>

<template>
	<div class="edit-poll-group">
		<ConfigBox :name="t('polls', 'Title')">
			<template #icon>
				<SpeakerIcon />
			</template>
			<InputDiv
				v-model="pollGroupTitle"
				focus
				type="text"
				:placeholder="t('polls', 'Enter Title')"
				:helper-text="helperTexts.title"
				@submit="updatePollGroup" />

			<div class="change-title-hint">
				<p v-if="titleUpdated">
					{{ helperTexts.titleChangedNote }}
				</p>
			</div>
		</ConfigBox>

		<ConfigBox :name="t('polls', 'Extended title')">
			<template #icon>
				<SpeakerBigIcon />
			</template>
			<InputDiv
				v-model="pollGroupTitleExt"
				type="text"
				:placeholder="t('polls', 'Enter extended Title')"
				:helper-text="helperTexts.titleExt"
				@submit="updatePollGroup" />
		</ConfigBox>

		<ConfigBox :name="t('polls', 'Description')">
			<template #icon>
				<DescriptionIcon />
			</template>
			<textarea
				v-model="pollGroupTitleDescription"
				class="input-textarea"
				:placeholder="t('polls', 'Enter a description')" />
			<p class="helper">
				{{ helperTexts.description }}
			</p>
		</ConfigBox>

		<div class="create-buttons">
			<NcButton @click="emit('close')">
				<template #default>
					{{ t('polls', 'Close') }}
				</template>
			</NcButton>
			<NcButton
				:disabled="disableEditButton"
				:variant="'primary'"
				@click="updatePollGroup">
				<template #default>
					{{ t('polls', 'Update') }}
				</template>
			</NcButton>
		</div>
	</div>
</template>

<style lang="scss">
.edit-poll-group {
	background-color: var(--color-main-background);
	padding: 8px 20px;

	.create-buttons {
		display: flex;
		justify-content: flex-end;
		gap: 8px;
	}

	.input-textarea {
		width: 99%;
		resize: vertical;
	}

	.helper {
		min-height: 1.5rem;
		font-size: 0.8em;
		opacity: 0.8;
	}
}
</style>
