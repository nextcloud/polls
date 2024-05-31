<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="user_settings">
		<RadioGroupDiv v-model="updateType" :options="updateTypeOptions" />
	</div>
</template>

<script>
import { RadioGroupDiv } from '../../Base/index.js'
import { writeValue } from '../../../mixins/adminSettingsMixin.js'
import { t } from '@nextcloud/l10n'

export default {
	name: 'AdminPerformance',

	components: {
		RadioGroupDiv,
	},

	mixins: [writeValue],

	data() {
		return {
			updateTypeOptions: [
				{ value: 'longPolling', label: t('polls', 'Enable "long polling" for instant updates') },
				{ value: 'periodicPolling', label: t('polls', 'Enable periodic requests of poll updates from the client') },
				{ value: 'noPolling', label: t('polls', 'Disable automatic updates (poll must be reloaded to get updates)') },
			],
		}
	},

	computed: {
		// Add bindings
		updateType: {
			get() {
				return this.appSettings.updateType
			},
			set(value) {
				this.writeValue({ updateType: value })
			},
		},
	},
}
</script>
