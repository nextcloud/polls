<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import CardDiv from '../Base/modules/CardDiv.vue'
import { t } from '@nextcloud/l10n'
import { usePollStore } from '../../stores/poll'
import { useSessionStore } from '@/stores/session'
import { NcCheckboxRadioSwitch, NcRadioGroup } from '@nextcloud/vue'

const pollStore = usePollStore()
const sessionStore = useSessionStore()
</script>

<template>
	<CardDiv type="info">
		<NcRadioGroup
			v-model="sessionStore.sessionSettings.timezoneName"
			class="radio-group"
			:label="t('polls', 'Select timezone to apply')">
			<NcCheckboxRadioSwitch value="poll">
				{{
					t('polls', 'Use original timezone ({pollTimezone})', {
						pollTimezone: pollStore.getTimezoneName,
					})
				}}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch value="local">
				{{
					t('polls', 'Use your timezone ({localTimezone})', {
						localTimezone:
							Intl.DateTimeFormat().resolvedOptions().timeZone,
					})
				}}
			</NcCheckboxRadioSwitch>
		</NcRadioGroup>
	</CardDiv>
</template>
