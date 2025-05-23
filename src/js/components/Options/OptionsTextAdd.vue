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

	methods: {
		async addOption() {
			if (this.newPollText) {
				try {
					await this.$store.dispatch('options/add', { text: this.newPollText })
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
		margin-inline-start: 40px;
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
