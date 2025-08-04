<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import { DateTime } from 'luxon'
import { t } from '@nextcloud/l10n'

import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import DateTimePicker from '../../components/Base/modules/DateTimePicker.vue'

import { usePollStore } from '../../stores/poll'

const pollStore = usePollStore()

const allowProposals = computed({
	get: () => pollStore.configuration.allowProposals === 'allow',
	set: (value) => {
		pollStore.configuration.allowProposals = value ? 'allow' : 'disallow'
		pollStore.write()
	},
})

const pollExpire = computed({
	get: () => pollStore.getProposalExpirationDateTime.toJSDate(),
	set: (value) => {
		pollStore.configuration.proposalsExpire =
			DateTime.fromJSDate(value).toSeconds()
		pollStore.write()
	},
})

const proposalExpiration = computed({
	get: () => !!pollStore.configuration.proposalsExpire,
	set: (value) => {
		if (value) {
			pollStore.configuration.proposalsExpire = DateTime.now()
				.plus({ week: 1 })
				.toSeconds()
		} else {
			pollStore.configuration.proposalsExpire = 0
		}
		pollStore.write()
	},
})
</script>

<template>
	<div>
		<NcCheckboxRadioSwitch v-model="allowProposals" type="switch">
			{{ t('polls', 'Allow proposals') }}
		</NcCheckboxRadioSwitch>

		<NcCheckboxRadioSwitch
			v-show="pollStore.isProposalAllowed"
			v-model="proposalExpiration"
			type="switch">
			{{ t('polls', 'Proposal closing date') }}
		</NcCheckboxRadioSwitch>

		<DateTimePicker
			v-show="proposalExpiration && pollStore.isProposalAllowed"
			v-model="pollExpire"
			type="datetime-local" />
	</div>
</template>
