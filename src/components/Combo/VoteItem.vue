<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="vote-item" :class="[answer, {empty: foreignOption}]">
		<VoteIndicator :answer="iconAnswer" />
	</div>
</template>

<script>
import { mapGetters } from 'vuex'
import VoteIndicator from '../VoteTable/VoteIndicator.vue'

export default {
	name: 'VoteItem',

	components: {
		VoteIndicator,
	},

	props: {
		option: {
			type: Object,
			default: undefined,
		},
		user: {
			type: Object,
			default: null,
		},
		pollId: {
			type: Number,
			default: 0,
		},
	},

	computed: {
		...mapGetters({
			optionBelongsToPoll: 'combo/optionBelongsToPoll',
		}),

		answer() {
			return this.$store.getters['combo/getVote']({
				option: this.option,
				user: this.user,
			}).answer
		},

		iconAnswer() {
			if (this.answer === 'no') {
				return (this.closed && this.option.confirmed) || this.isActive ? 'no' : ''
			}
			if (this.answer === '') {
				return (this.closed && this.option.confirmed) ? 'no' : ''
			}
			return this.answer
		},

		foreignOption() {
			return !this.optionBelongsToPoll({
				text: this.option.text,
				pollId: this.pollId,
			})
		},
	},
}

</script>

<style lang="scss" scoped>

.vote-item {
	display: flex;
	justify-content: center;
	align-items: center;
	background-color: var(--color-polls-background-no);

	&.empty {
		background-color: transparent;
	}

	&.yes {
		background-color: var(--color-polls-background-yes);
	}

	&.maybe {
		background-color: var(--color-polls-background-maybe);
	}

	&.no {
		background-color: var(--color-polls-background-no);
	}
}
</style>
