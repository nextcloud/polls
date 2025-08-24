<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="option-container">
		<!-- Menu to choose the rank for this poll -->
		<select v-model="selectedOption">
			<option
				v-for="(option, index) in internalChosenRank"
				:key="index"
				:value="option">
				{{ option }}
			</option>
		</select>
		<!-- text field to add a new value to the rank -->
		<NcTextField
			v-model="newOption"
			:placeholder="t('polls', 'Enter a new option')"
			:label="t('polls', 'New option')"
			class="nc-text-field" />
		<NcButton icon @click="addOption">
			<PlusIcon />
		</NcButton>
		<!-- Delete selected rank from the select -->
		<NcButton :disabled="!selectedOption" @click="removeOption">
			<CloseIcon />
		</NcButton>
	</div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { usePollStore } from '../../stores/poll.js'
import { t } from '@nextcloud/l10n'
import { NcButton, NcTextField } from '@nextcloud/vue'
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import CloseIcon from 'vue-material-design-icons/Close.vue'
import { showError } from '@nextcloud/dialogs'

const pollStore = usePollStore()

// Parse chosenRank string from store into array
const internalChosenRank = ref([])
const selectedOption = ref(null)
const newOption = ref('')

onMounted(() => {
	try {
		const initialValue = JSON.parse(pollStore.configuration.chosenRank || '[]')
		if (Array.isArray(initialValue)) {
			internalChosenRank.value = initialValue
		} else {
			internalChosenRank.value = [initialValue]
		}
		if (internalChosenRank.value.length > 0) {
			selectedOption.value = internalChosenRank.value[0]
		}
	} catch (e) {
		console.error('Erreur de parsing chosenRank:', e)
		internalChosenRank.value = []
	}
})

// update in store + API
async function updateChosenRank(newValue) {
	try {
		await pollStore.setChosenRank(newValue)
		await pollStore.write()
	} catch (err) {
		console.error('Update failed:', err)
		showError(t('polls', 'Failed to update options'))
	}
}

async function addOption() {
	const value = newOption.value.trim()
	if (value && !internalChosenRank.value.includes(value)) {
		const updated = [...internalChosenRank.value, value].sort()
		internalChosenRank.value = updated
		newOption.value = ''
		selectedOption.value = updated[0]
		await updateChosenRank(updated)
	}
}

async function removeOption() {
	const updated = internalChosenRank.value.filter(
		(o) => o !== selectedOption.value,
	)
	internalChosenRank.value = updated
	selectedOption.value = updated[0] || null
	await updateChosenRank(updated)
}
</script>

<style scoped>
.option-container {
	display: flex;
	align-items: center;
	gap: 8px;
	margin-bottom: 8px;
}

.nc-text-field {
	flex-grow: 1;
	margin-right: 8px;
	margin-bottom: 8px;
	width: 100px;
}

.option-item {
	display: flex;
	align-items: center;
	gap: 4px;
}
</style>
