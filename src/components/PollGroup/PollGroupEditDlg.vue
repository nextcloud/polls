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

import { ConfigBox } from '../Base/index.ts'

import { usePollGroupsStore } from '../../stores/pollGroups.ts'
import { showError } from '@nextcloud/dialogs'
import { useRoute } from 'vue-router'
import { router } from '../../router.ts'
import ConfigTitlePollGroup from './ConfigTitlePollGroup.vue'
import ConfigTitleExtPollGroup from './ConfigTitleExtPollGroup.vue'
import ConfigDescriptionPollGroup from './ConfigDescriptionPollGroup.vue'

const pollGroupsStore = usePollGroupsStore()

const route = useRoute()
const emit = defineEmits<{
	(e: 'close'): void
	(e: 'updated'): void
}>()

const newGroupAttributes = ref({
	slug: route.params.slug as string,
	title: pollGroupsStore.currentPollGroup?.title || '',
	titleExt: pollGroupsStore.currentPollGroup?.titleExt || '',
	description: pollGroupsStore.currentPollGroup?.description || '',
})

watch(route, (route) => {
	resetInputs(route.params.slug as string)
})

const pollGroupTitle = computed({
	get() {
		return pollGroupsStore.currentPollGroup?.title || ''
	},
	set(newTitle: string) {
		newGroupAttributes.value.title = newTitle
	},
})

const updating = ref(false)

const titleIsEmpty = computed(() => pollGroupTitle.value === '')
const disableEditButton = computed(() => titleIsEmpty.value || updating.value)

function resetInputs(slug: string) {
	newGroupAttributes.value = {
		slug,
		title: pollGroupsStore.currentPollGroup?.title || '',
		titleExt: pollGroupsStore.currentPollGroup?.titleExt || '',
		description: pollGroupsStore.currentPollGroup?.description || '',
	}
}

async function updatePollGroup() {
	try {
		// block the modal to prevent double submission
		updating.value = true

		pollGroupsStore.setCurrentPollGroup({
			...pollGroupsStore.currentPollGroup,
			...newGroupAttributes.value,
		})
		const pollGroup = await pollGroupsStore.writeCurrentPollGroup()

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

			<ConfigTitlePollGroup />
		</ConfigBox>

		<ConfigBox :name="t('polls', 'Extended title')">
			<template #icon>
				<SpeakerBigIcon />
			</template>
			<ConfigTitleExtPollGroup />
		</ConfigBox>

		<ConfigBox :name="t('polls', 'Description')">
			<template #icon>
				<DescriptionIcon />
			</template>
			<ConfigDescriptionPollGroup />
		</ConfigBox>
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
