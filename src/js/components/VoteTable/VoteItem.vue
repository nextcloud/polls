<!--
  - @copyright Copyright (c) 2018 René Gieling <github@dartcafe.de>
  -
  - @author René Gieling <github@dartcafe.de>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<div class="vote-item" :class="[answer, { active: isActive}]">
		<div v-if="isActive" class="icon" @click="voteClick()" />
		<div v-else class="icon" />
	</div>
</template>

<script>
import { mapState } from 'vuex'

export default {
	name: 'VoteItem',

	props: {
		option: {
			type: Object,
			default: undefined
		},
		userId: {
			type: String,
			default: ''
		}
	},

	computed: {
		...mapState({
			poll: state => state.poll,
			acl: state => state.acl
		}),

		answer() {
			try {
				return this.$store.getters.getVote({
					option: this.option,
					userId: this.userId
				}).voteAnswer
			} catch (e) {
				return ''
			}
		},

		isValidUser() {
			return (this.userId !== '' && this.userId !== null)
		},

		isActive() {
			return (this.isValidUser && this.acl.userId === this.userId && this.acl.allowVote)
		}

	},

	methods: {
		voteClick() {
			if (this.isActive) {
				this.$emit('voteClick')
			}
		}

	}
}

</script>

<style lang="scss">

	.vote-item {
		height: 43px;
		display: flex;
		width: 85px;
		align-items: center;
		background-color: var(--color-polls-background-no);
		color: var(--color-polls-foreground-no);
		> .icon {
			margin: auto;
			background-position: center;
			background-repeat: no-repeat;
			min-width: 30px;
			min-height: 30px;
			width: 30px;
			height: 30px;
			background-size: 90%;
			flex: 0 0 auto;
		}

		&.yes {
			background-color: var(--color-polls-background-yes);
			color: var(--color-polls-foreground-yes);
			> .icon {
				background-image: var(--icon-polls-yes)
			}
		}

		&.no {
			background-color: var(--color-polls-background-no);
			color: var(--color-polls-foreground-no);
			&.active > .icon {
				background-image: var(--icon-polls-no)
			}
		}

		&.maybe {
			background-color: var(--color-polls-background-maybe);
			color: var(--color-polls-foreground-maybe);
			> .icon {
				background-image: var(--icon-polls-maybe)
			}
		}

		&.active {
			background-color: var(--color-main-background);
			> .icon {
				cursor: pointer;
				border: 2px solid;
				border-radius: var(--border-radius);
			}
			&:active {
				box-shadow: inherit;
			}
		}

	}

	@media (max-width: (480px) ) {
		.vote-item {
			border-top: 1px solid var(--color-border-dark);
			&.active {
				width: 10vw;
				height: 10vw;
			}
		}
	}

</style>
