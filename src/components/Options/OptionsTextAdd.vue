<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<InputDiv v-model="newPollText"
		:placeholder="placeholder"
		submit
		@submit="addOption()" />
</template>

<script>
import { showError, showSuccess } from '@nextcloud/dialogs'
import { InputDiv } from '../Base/index.js'
import { t } from '@nextcloud/l10n'
import { mapStores } from 'pinia'
import { useOptionsStore } from '../../stores/options.ts'

export default {
	name: 'OptionsTextAdd',

	components: {
		InputDiv,
	},

	props: {
		placeholder: {
			type: String,
			default: t('polls', 'Add option'),
		},
	},

	data() {
		return {
			newPollText: '',
		}
	},

	computed: {
		...mapStores(useOptionsStore),
	},
	methods: {
		async addOption() {
			if (this.newPollText) {
				try {
					await this.optionsStore.add({ text: this.newPollText })
					showSuccess(t('polls', '{optionText} added', { optionText: this.newPollText }))
					this.newPollText = ''
				} catch (error) {
					if (error.response.status === 409) {
						showError(t('polls', '{optionText} already exists', { optionText: this.newPollText }))
					} else {
						showError(t('polls', 'Error adding {optionText}', { optionText: this.newPollText }))
					}
				}
			}
		},
	},
}
</script>

<style lang="scss">
	.optionAdd {
		display: flex;
	}

	.newOption {
		margin-left: 40px;
		flex: 1;
		&:empty:before {
			color: grey;
		}
	}

	.submit-option {
		width: 30px;
		background-color: transparent;
		border: none;
		opacity: 0.3;
		cursor: pointer;
	}

</style>
