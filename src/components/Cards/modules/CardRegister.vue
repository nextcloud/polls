<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<CardDiv :type="cardType">
		{{ registrationInvitationText }}
		<template #button>
			<ActionRegister />
		</template>
	</CardDiv>
</template>

<script>
import { mapStores } from 'pinia'
import { CardDiv } from '../../Base/index.js'
import ActionRegister from '../../Actions/modules/ActionRegister.vue'
import { t } from '@nextcloud/l10n'
import { useShareStore } from '../../../stores/share.ts'

export default {
	name: 'CardRegister',
	components: {
		CardDiv,
		ActionRegister,
	},

	data() {
		return {
			cardType: 'info',
		}
	},

	computed: {
		...mapStores(useShareStore),

		registrationInvitationText() {
			if (this.shareStore.publicPollEmail === 'mandatory') {
				return t('polls', 'To participate, register with your email address and a name.')
			}
			if (this.shareStore.publicPollEmail === 'optional') {
				return t('polls', 'To participate, register a name and optionally with your email address.')
			}
			return t('polls', 'To participate, register with a name.')
		},

	},
}
</script>
