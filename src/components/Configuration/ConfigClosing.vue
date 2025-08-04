<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import { DateTime } from 'luxon'
import { t } from '@nextcloud/l10n'

import NcButton from '@nextcloud/vue/components/NcButton'
import DateTimePicker from '../../components/Base/modules/DateTimePicker.vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'

import OpenPollIcon from 'vue-material-design-icons/LockOpenVariant.vue'
import ClosePollIcon from 'vue-material-design-icons/Lock.vue'

import { usePollStore } from '../../stores/poll'

const pollStore = usePollStore()

const expire = computed({
	get: () => pollStore.getExpirationDateTime.toJSDate(),
	set: (value) => {
		pollStore.configuration.expire = DateTime.fromJSDate(value).toSeconds()
		pollStore.write()
	},
})

const useExpire = computed({
	get: () => !!pollStore.configuration.expire,
	set: (value) => {
		if (value) {
			pollStore.configuration.expire = DateTime.now()
				.plus({ week: 1 })
				.toSeconds()
		} else {
			pollStore.configuration.expire = 0
		}
		pollStore.write()
	},
})

function clickToggleClosed() {
	if (pollStore.isClosed) {
		pollStore.reopen()
	} else {
		pollStore.close()
	}
}
</script>

<template>
	<div>
		<NcButton @click="clickToggleClosed()">
			<template #icon>
				<OpenPollIcon v-if="pollStore.isClosed" />
				<ClosePollIcon v-else />
			</template>
			<template #default>
				{{
					pollStore.isClosed
						? t('polls', 'Reopen poll')
						: t('polls', 'Close poll')
				}}
			</template>
		</NcButton>
		<NcCheckboxRadioSwitch
			v-show="!pollStore.isClosed"
			v-model="useExpire"
			type="switch">
			{{ t('polls', 'Poll closing date') }}
		</NcCheckboxRadioSwitch>
		<DateTimePicker
			v-show="useExpire && !pollStore.isClosed"
			v-model="expire"
			type="datetime-local" />
	</div>
</template>
