<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<NcActions>
			<NcActionButton :name="caption"
				:aria-label="caption"
				@click="showModal = true">
				<template #icon>
					<PasteIcon />
				</template>
			</NcActionButton>
		</NcActions>

		<NcModal v-if="showModal" size="small" no-close>
			<div class="option-clone-date modal__content">
				<h2>{{ t('polls', 'Create multiple options at once') }}</h2>
				<p>{{ t('polls', 'Each line creates a new option. Duplicates will get skipped without warning.') }}</p>

				<textarea v-model="newPollTexts"
					class="add-options-list"
					:placeholder="placeholder" />

				<div class="modal__buttons">
					<NcButton @click="showModal = false">
						<template #default>
							{{ t('polls', 'Close') }}
						</template>
					</NcButton>

					<NcButton variant="primary" @click="addOptionsList()">
						<template #default>
							{{ t('polls', 'OK') }}
						</template>
					</NcButton>
				</div>
			</div>
		</NcModal>
	</div>
</template>

<script>
import { showError, showSuccess } from '@nextcloud/dialogs'
import { NcActions, NcActionButton, NcButton, NcModal } from '@nextcloud/vue'
import PasteIcon from 'vue-material-design-icons/ClipboardTextMultiple.vue'

export default {
	name: 'OptionsTextAddBulk',

	components: {
		PasteIcon,
		NcActions,
		NcActionButton,
		NcModal,
		NcButton,
	},

	props: {
		placeholder: {
			type: String,
			default: t('polls', 'Add options list (one option per line)'),
		},
		caption: {
			type: String,
			default: t('polls', 'Paste option list'),
		},
	},

	data() {
		return {
			newPollTexts: '',
			showModal: false,
		}
	},

	methods: {
		async addOptionsList() {
			if (this.newPollTexts) {
				try {
					await this.$store.dispatch('options/addBulk', { text: this.newPollTexts })
					showSuccess(t('polls', 'Options added'))
					this.newPollTexts = ''
				} catch (error) {
					showError(t('polls', 'Error adding options', { optionText: this.newPollText }))
				}
			}
		},
	},
}
</script>

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
