<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<CardDiv :type="cardType">
		{{ t('polls', 'This poll is closed.') }}
		<span v-if="!allowEdit">
			{{ t('polls', 'No further action is possible.') }}
		</span>
	</CardDiv>
</template>

<script>
import { CardDiv } from '../../Base/index.js'
import { mapState } from 'vuex'

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
		...mapState({
			allowEdit: (state) => state.poll.permissions.edit,
		}),
	},

}
</script>
