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
	<div class="vote-item" :class="[iconClass, activeClass]" @click="voteClick()">
		<div class="icon" @click="voteClick()">

		</div>
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

	data() {
		return {
			answerSequence: []
		}
	},

	computed: {
		...mapState({
			poll: state => state.poll,
			event: state => state.event,
			votes: state => state.votes
		}),

		currentUser() {
			return OC.getCurrentUser().uid
		},

		getAnswer() {
			var index = this.votes.list.findIndex(vote =>
				vote.pollId == this.option.pollId
				&& vote.userId == this.userId
				&& vote.voteOptionText == this.option.text)
			if (index > -1) {
				return this.votes.list[index].voteAnswer
			} else {
				return 'unvoted'
			}
		},

		nextStatus() {
			var next = 'yes'
			if (this.getAnswer === 'yes') {
				next = 'no'
			} else if (this.getAnswer === 'maybe') {
				next = 'yes'
			} else if (this.getAnswer === 'no') {
				if (this.event.allowMaybe) {
					next = 'maybe'
				} else {
					next = 'yes'
				}
			}
			return next
		},

		activeClass() {
			if (this.currentUser === this.userId && this.poll.mode == 'vote') {
				return 'active'
			} else {
				return ''
			}
		},

		iconClass() {
			if (this.getAnswer === 'yes') {
				return 'yes icon-yes'
			} else if (this.getAnswer === 'maybe') {
				return 'maybe icon-maybe'
			} else if (this.getAnswer === 'no') {
				return 'no icon-no'
			} else {
				return ''
			}
		}

	},

	methods: {

		voteClick() {
			if (this.currentUser === this.userId && this.poll.mode == 'vote') {
				this.$store.dispatch('voteChange', { option: this.option, userId: this.userId, switchTo: this.nextStatus })
					.then(() => {
						this.$emit('voteSaved')
					})
			}
		}
	}
}
</script>

<style lang="scss" scoped>
	$bg-no: #ffede9;
	$bg-maybe: #fcf7e1;
	$bg-unvoted: #fff4c8;
	$bg-yes: #ebf5d6;

	$fg-no: #f45573;
	$fg-maybe: #f0db98;
	$fg-unvoted: #f0db98;
	$fg-yes: #49bc49;

	.vote-item {
		height: 43px;
		display: flex;
		flex: 1;
		width: 84px;
		// min-width: 85px;
		align-items: center;
		background-color: var(--color-background-dark);
		color: var(--color-main-text);
		> .icon {
			margin: auto;
			background-position: center;
			background-repeat: no-repeat;
			background-size: 32px;
			background-image: var(--icon-close-000);
			min-width: 40px;
			min-height: 40px;
			width: 40px;
			height: 40px;
			background-size: 90%;
			flex: 0 0 auto;
		}

		&.yes {
			background-color: $bg-yes;
			color: $fg-yes;
			// background-image: var(--icon-checkmark-49bc49);
			> .icon {
				background-image: url(data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGhlaWdodD0iMTYiIHdpZHRoPSIxNiIgdmVyc2lvbj0iMS4xIiB2aWV3Qm94PSIwIDAgMTYgMTYiPjxwYXRoIGQ9Im0yLjM1IDcuMyA0IDRsNy4zLTcuMyIgc3Ryb2tlPSIjNDliYzQ5IiBzdHJva2Utd2lkdGg9IjIiIGZpbGw9Im5vbmUiLz48L3N2Zz4K);
			}
		}

		&.no {
			background-color: $bg-no;
			color: $fg-no;
			// background-image: var(--icon-close-f45573);
			> .icon {
				background-image: url(data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGhlaWdodD0iMTYiIHdpZHRoPSIxNiIgdmVyc2lvbj0iMS4xIiB2aWV3Ym94PSIwIDAgMTYgMTYiPjxwYXRoIGQ9Im0xNCAxMi4zLTEuNyAxLjctNC4zLTQuMy00LjMgNC4zLTEuNy0xLjcgNC4zLTQuMy00LjMtNC4zIDEuNy0xLjcgNC4zIDQuMyA0LjMtNC4zIDEuNyAxLjctNC4zIDQuM3oiIGZpbGw9IiNmNDU1NzMiLz48L3N2Zz4K);
			}
		}

		&.maybe {
			background-color: $bg-maybe;
			color: $fg-maybe;
			// background-image: var(--icon-polls-maybe-vote-variant-f0db98);
			> .icon {
			background-image: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+CjxzdmcKICAgeG1sbnM6ZGM9Imh0dHA6Ly9wdXJsLm9yZy9kYy9lbGVtZW50cy8xLjEvIgogICB4bWxuczpjYz0iaHR0cDovL2NyZWF0aXZlY29tbW9ucy5vcmcvbnMjIgogICB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiCiAgIHhtbG5zOnN2Zz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciCiAgIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIKICAgeG1sbnM6c29kaXBvZGk9Imh0dHA6Ly9zb2RpcG9kaS5zb3VyY2Vmb3JnZS5uZXQvRFREL3NvZGlwb2RpLTAuZHRkIgogICB4bWxuczppbmtzY2FwZT0iaHR0cDovL3d3dy5pbmtzY2FwZS5vcmcvbmFtZXNwYWNlcy9pbmtzY2FwZSIKICAgaWQ9InN2ZzQiCiAgIHZlcnNpb249IjEuMSIKICAgd2lkdGg9IjE2IgogICBoZWlnaHQ9IjE2IgogICBzb2RpcG9kaTpkb2NuYW1lPSJtYXliZS12b3RlLXZhcmlhbnQuc3ZnIgogICBpbmtzY2FwZTp2ZXJzaW9uPSIwLjkyLjIgKDVjM2U4MGQsIDIwMTctMDgtMDYpIj4KICA8c29kaXBvZGk6bmFtZWR2aWV3CiAgICAgcGFnZWNvbG9yPSIjZmZmZmZmIgogICAgIGJvcmRlcmNvbG9yPSIjNjY2NjY2IgogICAgIGJvcmRlcm9wYWNpdHk9IjEiCiAgICAgb2JqZWN0dG9sZXJhbmNlPSIxMCIKICAgICBncmlkdG9sZXJhbmNlPSIxMCIKICAgICBndWlkZXRvbGVyYW5jZT0iMTAiCiAgICAgaW5rc2NhcGU6cGFnZW9wYWNpdHk9IjAiCiAgICAgaW5rc2NhcGU6cGFnZXNoYWRvdz0iMiIKICAgICBpbmtzY2FwZTp3aW5kb3ctd2lkdGg9IjE5MjAiCiAgICAgaW5rc2NhcGU6d2luZG93LWhlaWdodD0iMTAxNyIKICAgICBpZD0ibmFtZWR2aWV3NiIKICAgICBzaG93Z3JpZD0iZmFsc2UiCiAgICAgaW5rc2NhcGU6em9vbT0iMTQuNzUiCiAgICAgaW5rc2NhcGU6Y3g9IjgiCiAgICAgaW5rc2NhcGU6Y3k9IjE0Ljg2NTIwMSIKICAgICBpbmtzY2FwZTp3aW5kb3cteD0iLTgiCiAgICAgaW5rc2NhcGU6d2luZG93LXk9Ii04IgogICAgIGlua3NjYXBlOndpbmRvdy1tYXhpbWl6ZWQ9IjEiCiAgICAgaW5rc2NhcGU6Y3VycmVudC1sYXllcj0ic3ZnNCI+CiAgICA8aW5rc2NhcGU6Z3JpZAogICAgICAgdHlwZT0ieHlncmlkIgogICAgICAgaWQ9ImdyaWQ4MzYiIC8+CiAgPC9zb2RpcG9kaTpuYW1lZHZpZXc+CiAgPG1ldGFkYXRhCiAgICAgaWQ9Im1ldGFkYXRhMTAiPgogICAgPHJkZjpSREY+CiAgICAgIDxjYzpXb3JrCiAgICAgICAgIHJkZjphYm91dD0iIj4KICAgICAgICA8ZGM6Zm9ybWF0PmltYWdlL3N2Zyt4bWw8L2RjOmZvcm1hdD4KICAgICAgICA8ZGM6dHlwZQogICAgICAgICAgIHJkZjpyZXNvdXJjZT0iaHR0cDovL3B1cmwub3JnL2RjL2RjbWl0eXBlL1N0aWxsSW1hZ2UiIC8+CiAgICAgICAgPGRjOnRpdGxlPjwvZGM6dGl0bGU+CiAgICAgIDwvY2M6V29yaz4KICAgIDwvcmRmOlJERj4KICA8L21ldGFkYXRhPgogIDxkZWZzCiAgICAgaWQ9ImRlZnM4IiAvPgogIDx0ZXh0CiAgICAgeG1sOnNwYWNlPSJwcmVzZXJ2ZSIKICAgICBzdHlsZT0iZm9udC1zdHlsZTpub3JtYWw7Zm9udC13ZWlnaHQ6bm9ybWFsO2ZvbnQtc2l6ZToxNS4wMDY0OTA3MXB4O2xpbmUtaGVpZ2h0OjEuMjU7Zm9udC1mYW1pbHk6c2Fucy1zZXJpZjtsZXR0ZXItc3BhY2luZzowcHg7d29yZC1zcGFjaW5nOjBweDtmaWxsOiNmZmMxMDc7ZmlsbC1vcGFjaXR5OjE7c3Ryb2tlOm5vbmU7c3Ryb2tlLXdpZHRoOjEuMDIzMTY5NzYiCiAgICAgeD0iLTAuODAzNjg5NDgiCiAgICAgeT0iMTIuNTU5MzEyIgogICAgIGlkPSJ0ZXh0ODE4IgogICAgIHRyYW5zZm9ybT0ic2NhbGUoMS4wOTAxOSwwLjkxNzI3MTMpIj48dHNwYW4KICAgICAgIHNvZGlwb2RpOnJvbGU9ImxpbmUiCiAgICAgICBpZD0idHNwYW44MTYiCiAgICAgICB4PSItMC44MDM2ODk0OCIKICAgICAgIHk9IjEyLjU1OTMxMiIKICAgICAgIHN0eWxlPSJmb250LXNpemU6MTQuNjY2NjY2OThweDtmaWxsOiNmZmMxMDc7ZmlsbC1vcGFjaXR5OjE7c3Ryb2tlLXdpZHRoOjEuMDIzMTY5NzYiPig8L3RzcGFuPjwvdGV4dD4KICA8dGV4dAogICAgIHhtbDpzcGFjZT0icHJlc2VydmUiCiAgICAgc3R5bGU9ImZvbnQtc3R5bGU6bm9ybWFsO2ZvbnQtd2VpZ2h0Om5vcm1hbDtmb250LXNpemU6NDAuOTI3MTQzMXB4O2xpbmUtaGVpZ2h0OjEuMjU7Zm9udC1mYW1pbHk6c2Fucy1zZXJpZjtsZXR0ZXItc3BhY2luZzowcHg7d29yZC1zcGFjaW5nOjBweDtmaWxsOiNmZmMxMDc7ZmlsbC1vcGFjaXR5OjE7c3Ryb2tlOm5vbmU7c3Ryb2tlLXdpZHRoOjEuMDIzMTc4NDYiCiAgICAgeD0iOS45NjMwNDQyIgogICAgIHk9IjEyLjQ3ODk0NSIKICAgICBpZD0idGV4dDgyOCIKICAgICB0cmFuc2Zvcm09InNjYWxlKDEuMDkwMTk5MywwLjkxNzI2MzQ4KSI+PHRzcGFuCiAgICAgICBzb2RpcG9kaTpyb2xlPSJsaW5lIgogICAgICAgaWQ9InRzcGFuODI2IgogICAgICAgeD0iOS45NjMwNDQyIgogICAgICAgeT0iMTIuNDc4OTQ1IgogICAgICAgc3R5bGU9ImZvbnQtc2l6ZToxNC42NjY2NjY5OHB4O2ZpbGw6I2ZmYzEwNztmaWxsLW9wYWNpdHk6MTtzdHJva2Utd2lkdGg6MS4wMjMxNzg0NiI+KTwvdHNwYW4+PC90ZXh0PgogIDxwYXRoCiAgICAgaW5rc2NhcGU6Y29ubmVjdG9yLWN1cnZhdHVyZT0iMCIKICAgICBkPSJtIDExLjkyNCw0LjA2NTk5OTIgLTQuOTMyMDAwMSw0Ljk3IC0yLjgyOCwtMi44MyBMIDIuNzUsNy42MTc5OTkyIDYuOTkxOTk5OSwxMS44NjEgMTMuMzU3LDUuNDk1OTk5MiBsIC0xLjQzMywtMS40MzIgeiIKICAgICBpZD0icGF0aDgxNiIKICAgICBzdHlsZT0iZmlsbDojZmZjMTA3O2ZpbGwtb3BhY2l0eToxIiAvPgo8L3N2Zz4K);
			}
		}

		&.unvoted {
			background-color: $bg-no;
			color: $fg-no;
			&:before {
				content: attr(data-unvoted);
				color: $fg-no;
				font-size: 11px;
				font-weight: bold;
				line-height: 25px;
			}
		}

		&.active {
			background-color: var(--color-main-background);
			> .icon {
				cursor: pointer;
				border: 2px solid;
				border-radius: var(--border-radius);
			}
			// &.icon-no {
			// 	background-image: initial;
			// }
			&.unvoted {
				background-color: $bg-maybe;
				color: $fg-maybe;
			}
			&:active {
				box-shadow: inherit;
			}
		}

	}

	@media (max-width: (480px) ) {
		.vote-item {

			&.active {
				width: 10vw;
				height: 10vw;
			}
		}
	}

</style>
