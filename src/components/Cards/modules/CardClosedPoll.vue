<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<CardDiv :type="cardType">
		{{ t('polls', 'This poll is closed.') }}
		<span v-if="!pollStore.permissions.allowEdit">
			{{ t('polls', 'No further action is possible.') }}
		</span>
	</CardDiv>
</template>

<script>
import { mapStores } from 'pinia'
import { CardDiv } from '../../Base/index.js'
import { t } from '@nextcloud/l10n'
import { usePollStore } from '../../../stores/poll.ts'

export default {
	name: 'CardClosedPoll',

	components: {
		CardDiv,
	},

	data() {
		return {
			cardType: 'warning',
		}
	},

	computed: {
		...mapStores(usePollStore),
	},

	methods: {
		t,
	},

}
</script>
