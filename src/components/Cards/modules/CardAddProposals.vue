<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { CardDiv } from '../../Base/index.js'
import OptionProposals from '../../Options/OptionProposals.vue'
import { t } from '@nextcloud/l10n'
import { usePollStore, PollType } from '../../../stores/poll.ts'
import ActionAddOption from '../../Actions/modules/ActionAddOption.vue'
import { usePreferencesStore } from '../../../stores/preferences.ts'

const pollStore = usePollStore()
const preferencesStore = usePreferencesStore()
const cardType = 'info'

const optionAddDatesModalProps = {
	caption: t('polls', 'Add'),
	showCaption: true,
	primary: true,
}
</script>

<template>
	<CardDiv :type="cardType">
		{{ t('polls', 'You are asked to propose more options. ') }}
		<p v-if="pollStore.isProposalExpirySet && !pollStore.isProposalExpired">
			{{
				t('polls', 'The proposal period ends {timeRelative}.', {
					timeRelative: pollStore.proposalsExpireRelative,
				})
			}}
		</p>
		<OptionProposals v-if="pollStore.type === PollType.Text" />
		<template v-if="pollStore.type === PollType.Date" #button>
			<ActionAddOption
				v-if="preferencesStore.user.useNewAddOption"
				v-bind="optionAddDatesModalProps" />
			<OptionProposals v-else />
		</template>
	</CardDiv>
</template>
