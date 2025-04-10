<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import moment from '@nextcloud/moment'
import { t } from '@nextcloud/l10n'

import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import DateTimePicker from '../../components/Base/modules/DateTimePicker.vue'

import { usePollStore, AllowProposals } from '../../stores/poll.ts'

const pollStore = usePollStore()

const allowProposals = computed({
	get: () => pollStore.configuration.allowProposals === AllowProposals.Allow,
	set: (value) => {
		pollStore.configuration.allowProposals = value
			? AllowProposals.Allow
			: AllowProposals.Disallow
		pollStore.write()
	},
})

const pollExpire = computed({
	get: () => moment.unix(pollStore.configuration.proposalsExpire)._d,
	set: (value) => {
		pollStore.configuration.proposalsExpire = moment(value).unix()
		pollStore.write()
	},
})

const proposalExpiration = computed({
	get: () => !!pollStore.configuration.proposalsExpire,
	set: (value) => {
		if (value) {
			pollStore.configuration.proposalsExpire = moment().add(1, 'week').unix()
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
			{{ t('polls', 'Allow Proposals') }}
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
