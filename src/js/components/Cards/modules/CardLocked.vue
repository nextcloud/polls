<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<CardDiv :type="cardType">
		{{ cardText }}
	</CardDiv>
</template>

<script>
import { CardDiv } from '../../Base/index.js'
import { t } from '@nextcloud/l10n'

export default {
	name: 'CardLocked',

	components: {
		CardDiv,
	},

	data() {
		return {
			cardType: 'warning',
		}
	},

	computed: {
		cardText() {
			return this.$route.name === 'publicVote' ? t('polls', 'This share is locked and allows only read access. Registering is not possible.') : t('polls', 'Voting is locked and you have just read access to this poll.')
		},
	},
}
</script>
