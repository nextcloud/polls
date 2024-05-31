<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<div class="user_settings">
			<NcCheckboxRadioSwitch :checked.sync="defaultViewTextPoll" type="switch">
				{{ t('polls', 'Text polls default to list view') }}
			</NcCheckboxRadioSwitch>
			<div class="settings_details">
				{{ t('polls', 'Check this, if you prefer to display text poll in a vertical aligned list rather than in the grid view. The initial default is list view.') }}
			</div>
		</div>

		<div class="user_settings">
			<NcCheckboxRadioSwitch :checked.sync="defaultViewDatePoll" type="switch">
				{{ t('polls', 'Date polls default to list view') }}
			</NcCheckboxRadioSwitch>
			<div class="settings_details">
				{{ t('polls', 'Check this, if you prefer to display date poll in a vertical view rather than in the grid view. The initial default is grid view.') }}
			</div>
		</div>

		<div class="user_settings">
			<InputDiv v-model="relevantOffset"
				type="number"
				inputmode="numeric"
				use-num-modifiers
				:label="t('polls', 'Enter the amount of days, polls without activity stay in the relevant list:')" />
		</div>
	</div>
</template>

<script>

import { mapState } from 'vuex'
import { InputDiv } from '../../Base/index.js'
import { NcCheckboxRadioSwitch } from '@nextcloud/vue'
import { t } from '@nextcloud/l10n'

export default {
	name: 'FeatureSettings',

	components: {
		NcCheckboxRadioSwitch,
		InputDiv,
	},

	computed: {
		...mapState({
			settings: (state) => state.settings.user,
		}),

		relevantOffset: {
			get() {
				return this.settings.relevantOffset
			},
			set(value) {
				value = value < 1 ? 1 : value
				this.writeValue({ relevantOffset: value })
			},
		},

		defaultViewTextPoll: {
			get() {
				return (this.settings.defaultViewTextPoll === 'list-view')
			},
			set(value) {
				if (value) {
					this.writeValue({ defaultViewTextPoll: 'list-view' })
				} else {
					this.writeValue({ defaultViewTextPoll: 'table-view' })
				}
			},
		},
		defaultViewDatePoll: {
			get() {
				return (this.settings.defaultViewDatePoll === 'list-view')
			},
			set(value) {
				if (value) {
					this.writeValue({ defaultViewDatePoll: 'list-view' })
				} else {
					this.writeValue({ defaultViewDatePoll: 'table-view' })
				}
			},
		},
	},

	methods: {
		t,
		async writeValue(value) {
			await this.$store.commit('settings/setPreference', value)
			this.$store.dispatch('settings/write')
		},
	},
}
</script>
