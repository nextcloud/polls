<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { ref } from 'vue'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'

import NcModal from '@nextcloud/vue/components/NcModal'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcButton from '@nextcloud/vue/components/NcButton'

import PasteIcon from 'vue-material-design-icons/ClipboardTextMultiple.vue'

import { useOptionsStore } from '../../stores/options.ts'

const optionsStore = useOptionsStore()

const newPollTexts = ref('')
const showModal = ref(false)

const props = defineProps({
	placeholder: {
		type: String,
		default: t('polls', 'Add options list (one option per line)'),
	},
	caption: {
		type: String,
		default: t('polls', 'Paste option list'),
	},
})

/**
 *
 */
async function addOptionsList() {
	if (newPollTexts.value) {
		try {
			await optionsStore.addBulk({ text: newPollTexts.value })
			showSuccess(t('polls', 'Options added'))
			newPollTexts.value = ''
		} catch (error) {
			showError(
				t('polls', 'Error adding options', {
					optionText: newPollTexts.value,
				}),
			)
		}
	}
}
</script>

<template>
	<div>
		<NcActions>
			<NcActionButton
				:name="props.caption"
				:aria-label="props.caption"
				@click="showModal = true">
				<template #icon>
					<PasteIcon />
				</template>
			</NcActionButton>
		</NcActions>

		<NcModal v-if="showModal" size="small" :can-close="false">
			<div class="option-clone-date modal__content">
				<h2>{{ t('polls', 'Create multiple options at once') }}</h2>
				<p>
					{{
						t(
							'polls',
							'Each line creates a new option. Duplicates will get skipped without warning.',
						)
					}}
				</p>

				<textarea
					v-model="newPollTexts"
					class="add-options-list"
					:placeholder="props.placeholder" />

				<div class="modal__buttons">
					<NcButton @click="showModal = false">
						<template #default>
							{{ t('polls', 'Close') }}
						</template>
					</NcButton>

					<NcButton
						:variant="'primary'"
						@click="addOptionsList()">
						<template #default>
							{{ t('polls', 'OK') }}
						</template>
					</NcButton>
				</div>
			</div>
		</NcModal>
	</div>
</template>

<style lang="scss">
.option-clone-date.modal__content {
	padding-left: 28px;
	padding-right: 28px;
}

.add-options-list {
	width: 99%;
	resize: vertical;
	height: 210px;
}
</style>
