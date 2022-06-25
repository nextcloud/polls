<!--
  - @copyright Copyright (c) 2018 René Gieling <github@dartcafe.de>
  -
  - @author René Gieling <github@dartcafe.de>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<div>
		<Actions>
			<ActionButton @click="showModal = true">
				<template #icon>
					<PasteIcon />
				</template>
				{{ caption }}
			</ActionButton>
		</Actions>

		<Modal v-if="showModal" size="small" :can-close="false">
			<div class="option-clone-date modal__content">
				<h2>{{ t('polls', 'Create multiple options at once') }}</h2>

				<p>{{ t('polls', 'Each line creates a new option. Duplicates will get skipped without warning.') }}</p>

				<textarea v-model="newPollTexts"
					class="add-options-list"
					:placeholder="placeholder" />

				<div class="modal__buttons">
					<VueButton @click="showModal = false">
						{{ t('polls', 'Close') }}
					</VueButton>

					<VueButton type="primary" @click="addOptionsList()">
						{{ t('polls', 'OK') }}
					</VueButton>
				</div>
			</div>
		</Modal>
	</div>
</template>

<script>
import { showError, showSuccess } from '@nextcloud/dialogs'
import { Actions, ActionButton, Button as VueButton, Modal } from '@nextcloud/vue'
import PasteIcon from 'vue-material-design-icons/ClipboardTextMultiple.vue'

export default {
	name: 'OptionsTextAddBulk',

	components: {
		PasteIcon,
		Actions,
		ActionButton,
		Modal,
		VueButton,
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
				} catch (e) {
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
