<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { CardDiv } from '../../Base/index.ts'
import { t } from '@nextcloud/l10n'
import { usePollStore, PollType } from '../../../stores/poll.ts'
import ActionAddOption from '../../Actions/modules/ActionAddOption.vue'
import OptionsTextAdd from '../../Options/OptionsTextAdd.vue'

const pollStore = usePollStore()
const cardType = 'info'

const optionAddDatesModalProps = {
	caption: t('polls', 'Add'),
	showCaption: true,
	primary: true,
}
</script>

<template>
	<CardDiv :type="cardType">
		{{ t('polls', 'You are asked to propose more options.') }}
		<p v-if="pollStore.isProposalExpirySet && !pollStore.isProposalExpired">
			{{
				t('polls', 'The proposal period ends {timeRelative}.', {
					timeRelative: pollStore.proposalsExpireRelative,
				})
			}}
		</p>

		<OptionsTextAdd
			v-if="pollStore.type === PollType.Text"
			:placeholder="t('polls', 'Propose an option')" />

		<template v-if="pollStore.type === PollType.Date" #button>
			<ActionAddOption v-bind="optionAddDatesModalProps" />
		</template>
	</CardDiv>
</template>
