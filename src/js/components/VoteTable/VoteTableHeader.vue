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
	<div class="vote-table-header" :class=" { winner: isWinner }">
		<div v-if="poll.type === 'textPoll'" class="text-box">
			{{ option.pollOptionText }}
		</div>

		<div v-if="poll.type === 'datePoll'" v-tooltip.auto="moment.unix(option.timestamp).format('llll')" class="date-box">
			<div class="month">
				{{ moment.unix(option.timestamp).format('MMM') + " '" + moment.unix(option.timestamp).format('YY') }}
			</div>
			<div class="day">
				{{ moment.unix(option.timestamp).format('Do') }}
			</div>
			<div class="dow">
				{{ moment.unix(option.timestamp).format('ddd') }}
			</div>
			<div class="time">
				{{ moment.unix(option.timestamp).format('LT') }}
			</div>
		</div>

		<div class="counter">
			<div class="yes">
				<span> {{ yesVotes }} </span>
			</div>
			<div v-if="poll.allowMaybe" class="maybe">
				<span> {{ maybeVotes }} </span>
			</div>
		</div>
	</div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'

export default {
	name: 'VoteTableHeader',

	props: {
		option: {
			type: Object,
			default: undefined
		},
		pollType: {
			type: String,
			default: undefined
		}
	},

	data() {
		return {
			openedMenu: false,
			hostName: this.$route.query.page
		}

	},

	computed: {
		...mapState({
			poll: state => state.poll,
			votes: state => state.votes.list
		}),
		...mapGetters([
			'votesRank',
			'winnerCombo'
		]),

		yesVotes() {
			const pollOptionText = this.option.pollOptionText
			return this.votesRank.find(rank => {
				return rank.pollOptionText === pollOptionText
			}).yes
		},

		maybeVotes() {
			const pollOptionText = this.option.pollOptionText
			return this.votesRank.find(rank => {
				return rank.pollOptionText === pollOptionText
			}).maybe
		},

		isWinner() {
			const pollOptionText = this.option.pollOptionText
			return (
				this.votesRank.find(rank => {
					return rank.pollOptionText === pollOptionText
				}).yes === this.winnerCombo.yes

				&& (this.votesRank.find(rank => {
					return rank.pollOptionText === pollOptionText
				}).yes + this.votesRank.find(rank => {
					return rank.pollOptionText === pollOptionText
				}).maybe > 0)

				&& this.winnerCombo.maybe === this.votesRank.find(rank => {
					return rank.pollOptionText === pollOptionText
				}).maybe
			)
		}
	}
}

</script>

<style lang="scss" scoped>

.vote-table-header {
	// display: flex;
	// flex-direction: column;
	&.winner {
		font-weight: bold;
		color: #49bc49;
	}
}

.counter {
	flex: 0;
	display: flex;
	justify-content: center;
	font-size: 18px;
	// height: 88px;
	padding: 14px 4px;

	&> * {
		background-position: 0px 2px;
		padding-left: 23px;
		background-repeat: no-repeat;
		background-size: contain;
		margin-right: 8px;
		// min-height: 24px;
	}

	.yes {
		color: #49bc49;
		background-image: url(data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGhlaWdodD0iMTYiIHdpZHRoPSIxNiIgdmVyc2lvbj0iMS4xIiB2aWV3Qm94PSIwIDAgMTYgMTYiPjxwYXRoIGQ9Im0yLjM1IDcuMyA0IDRsNy4zLTcuMyIgc3Ryb2tlPSIjNDliYzQ5IiBzdHJva2Utd2lkdGg9IjIiIGZpbGw9Im5vbmUiLz48L3N2Zz4K);
	}
	.no {
		color: #f45573;
		background-image: url(data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGhlaWdodD0iMTYiIHdpZHRoPSIxNiIgdmVyc2lvbj0iMS4xIiB2aWV3Ym94PSIwIDAgMTYgMTYiPjxwYXRoIGQ9Im0xNCAxMi4zLTEuNyAxLjctNC4zLTQuMy00LjMgNC4zLTEuNy0xLjcgNC4zLTQuMy00LjMtNC4zIDEuNy0xLjcgNC4zIDQuMyA0LjMtNC4zIDEuNyAxLjctNC4zIDQuM3oiIGZpbGw9IiNmNDU1NzMiLz48L3N2Zz4K);
	}
	.maybe {
		color: #ffc107;
		background-image: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+CjxzdmcKICAgeG1sbnM6ZGM9Imh0dHA6Ly9wdXJsLm9yZy9kYy9lbGVtZW50cy8xLjEvIgogICB4bWxuczpjYz0iaHR0cDovL2NyZWF0aXZlY29tbW9ucy5vcmcvbnMjIgogICB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiCiAgIHhtbG5zOnN2Zz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciCiAgIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIKICAgeG1sbnM6c29kaXBvZGk9Imh0dHA6Ly9zb2RpcG9kaS5zb3VyY2Vmb3JnZS5uZXQvRFREL3NvZGlwb2RpLTAuZHRkIgogICB4bWxuczppbmtzY2FwZT0iaHR0cDovL3d3dy5pbmtzY2FwZS5vcmcvbmFtZXNwYWNlcy9pbmtzY2FwZSIKICAgaWQ9InN2ZzQiCiAgIHZlcnNpb249IjEuMSIKICAgd2lkdGg9IjE2IgogICBoZWlnaHQ9IjE2IgogICBzb2RpcG9kaTpkb2NuYW1lPSJtYXliZS12b3RlLXZhcmlhbnQuc3ZnIgogICBpbmtzY2FwZTp2ZXJzaW9uPSIwLjkyLjIgKDVjM2U4MGQsIDIwMTctMDgtMDYpIj4KICA8c29kaXBvZGk6bmFtZWR2aWV3CiAgICAgcGFnZWNvbG9yPSIjZmZmZmZmIgogICAgIGJvcmRlcmNvbG9yPSIjNjY2NjY2IgogICAgIGJvcmRlcm9wYWNpdHk9IjEiCiAgICAgb2JqZWN0dG9sZXJhbmNlPSIxMCIKICAgICBncmlkdG9sZXJhbmNlPSIxMCIKICAgICBndWlkZXRvbGVyYW5jZT0iMTAiCiAgICAgaW5rc2NhcGU6cGFnZW9wYWNpdHk9IjAiCiAgICAgaW5rc2NhcGU6cGFnZXNoYWRvdz0iMiIKICAgICBpbmtzY2FwZTp3aW5kb3ctd2lkdGg9IjE5MjAiCiAgICAgaW5rc2NhcGU6d2luZG93LWhlaWdodD0iMTAxNyIKICAgICBpZD0ibmFtZWR2aWV3NiIKICAgICBzaG93Z3JpZD0iZmFsc2UiCiAgICAgaW5rc2NhcGU6em9vbT0iMTQuNzUiCiAgICAgaW5rc2NhcGU6Y3g9IjgiCiAgICAgaW5rc2NhcGU6Y3k9IjE0Ljg2NTIwMSIKICAgICBpbmtzY2FwZTp3aW5kb3cteD0iLTgiCiAgICAgaW5rc2NhcGU6d2luZG93LXk9Ii04IgogICAgIGlua3NjYXBlOndpbmRvdy1tYXhpbWl6ZWQ9IjEiCiAgICAgaW5rc2NhcGU6Y3VycmVudC1sYXllcj0ic3ZnNCI+CiAgICA8aW5rc2NhcGU6Z3JpZAogICAgICAgdHlwZT0ieHlncmlkIgogICAgICAgaWQ9ImdyaWQ4MzYiIC8+CiAgPC9zb2RpcG9kaTpuYW1lZHZpZXc+CiAgPG1ldGFkYXRhCiAgICAgaWQ9Im1ldGFkYXRhMTAiPgogICAgPHJkZjpSREY+CiAgICAgIDxjYzpXb3JrCiAgICAgICAgIHJkZjphYm91dD0iIj4KICAgICAgICA8ZGM6Zm9ybWF0PmltYWdlL3N2Zyt4bWw8L2RjOmZvcm1hdD4KICAgICAgICA8ZGM6dHlwZQogICAgICAgICAgIHJkZjpyZXNvdXJjZT0iaHR0cDovL3B1cmwub3JnL2RjL2RjbWl0eXBlL1N0aWxsSW1hZ2UiIC8+CiAgICAgICAgPGRjOnRpdGxlPjwvZGM6dGl0bGU+CiAgICAgIDwvY2M6V29yaz4KICAgIDwvcmRmOlJERj4KICA8L21ldGFkYXRhPgogIDxkZWZzCiAgICAgaWQ9ImRlZnM4IiAvPgogIDx0ZXh0CiAgICAgeG1sOnNwYWNlPSJwcmVzZXJ2ZSIKICAgICBzdHlsZT0iZm9udC1zdHlsZTpub3JtYWw7Zm9udC13ZWlnaHQ6bm9ybWFsO2ZvbnQtc2l6ZToxNS4wMDY0OTA3MXB4O2xpbmUtaGVpZ2h0OjEuMjU7Zm9udC1mYW1pbHk6c2Fucy1zZXJpZjtsZXR0ZXItc3BhY2luZzowcHg7d29yZC1zcGFjaW5nOjBweDtmaWxsOiNmZmMxMDc7ZmlsbC1vcGFjaXR5OjE7c3Ryb2tlOm5vbmU7c3Ryb2tlLXdpZHRoOjEuMDIzMTY5NzYiCiAgICAgeD0iLTAuODAzNjg5NDgiCiAgICAgeT0iMTIuNTU5MzEyIgogICAgIGlkPSJ0ZXh0ODE4IgogICAgIHRyYW5zZm9ybT0ic2NhbGUoMS4wOTAxOSwwLjkxNzI3MTMpIj48dHNwYW4KICAgICAgIHNvZGlwb2RpOnJvbGU9ImxpbmUiCiAgICAgICBpZD0idHNwYW44MTYiCiAgICAgICB4PSItMC44MDM2ODk0OCIKICAgICAgIHk9IjEyLjU1OTMxMiIKICAgICAgIHN0eWxlPSJmb250LXNpemU6MTQuNjY2NjY2OThweDtmaWxsOiNmZmMxMDc7ZmlsbC1vcGFjaXR5OjE7c3Ryb2tlLXdpZHRoOjEuMDIzMTY5NzYiPig8L3RzcGFuPjwvdGV4dD4KICA8dGV4dAogICAgIHhtbDpzcGFjZT0icHJlc2VydmUiCiAgICAgc3R5bGU9ImZvbnQtc3R5bGU6bm9ybWFsO2ZvbnQtd2VpZ2h0Om5vcm1hbDtmb250LXNpemU6NDAuOTI3MTQzMXB4O2xpbmUtaGVpZ2h0OjEuMjU7Zm9udC1mYW1pbHk6c2Fucy1zZXJpZjtsZXR0ZXItc3BhY2luZzowcHg7d29yZC1zcGFjaW5nOjBweDtmaWxsOiNmZmMxMDc7ZmlsbC1vcGFjaXR5OjE7c3Ryb2tlOm5vbmU7c3Ryb2tlLXdpZHRoOjEuMDIzMTc4NDYiCiAgICAgeD0iOS45NjMwNDQyIgogICAgIHk9IjEyLjQ3ODk0NSIKICAgICBpZD0idGV4dDgyOCIKICAgICB0cmFuc2Zvcm09InNjYWxlKDEuMDkwMTk5MywwLjkxNzI2MzQ4KSI+PHRzcGFuCiAgICAgICBzb2RpcG9kaTpyb2xlPSJsaW5lIgogICAgICAgaWQ9InRzcGFuODI2IgogICAgICAgeD0iOS45NjMwNDQyIgogICAgICAgeT0iMTIuNDc4OTQ1IgogICAgICAgc3R5bGU9ImZvbnQtc2l6ZToxNC42NjY2NjY5OHB4O2ZpbGw6I2ZmYzEwNztmaWxsLW9wYWNpdHk6MTtzdHJva2Utd2lkdGg6MS4wMjMxNzg0NiI+KTwvdHNwYW4+PC90ZXh0PgogIDxwYXRoCiAgICAgaW5rc2NhcGU6Y29ubmVjdG9yLWN1cnZhdHVyZT0iMCIKICAgICBkPSJtIDExLjkyNCw0LjA2NTk5OTIgLTQuOTMyMDAwMSw0Ljk3IC0yLjgyOCwtMi44MyBMIDIuNzUsNy42MTc5OTkyIDYuOTkxOTk5OSwxMS44NjEgMTMuMzU3LDUuNDk1OTk5MiBsIC0xLjQzMywtMS40MzIgeiIKICAgICBpZD0icGF0aDgxNiIKICAgICBzdHlsZT0iZmlsbDojZmZjMTA3O2ZpbGwtb3BhY2l0eToxIiAvPgo8L3N2Zz4K);
	}
}

.text-box {
	flex: 1 0;
	height: 44px;
	align-self: center;
	font-size: 1.2em;
	padding-top: 14px;
	hyphens: auto;
}

.date-box {
	// display: flex;
	// flex-direction: column;
	// flex: 1 0;
	padding: 0 2px;
	align-items: center;
	justify-content: center;
	text-align: center;

	.month, .dow {
		font-size: 1.1em;
		color: var(--color-text-lighter);
	}
	.day {
		font-size: 1.4em;
		margin: 5px 0 5px 0;
	}
}

@media (max-width: (480px) ) {
	.vote-table-header {
		padding: 4px 0;
		display: flex;
		flex-direction: row;
		justify-content: space-around;
		border-top: 1px solid var(--color-border-dark);

		.date-box {
			padding: 0 20px 0 4px;
			align-content: center;
		}
		.counter {
			flex-direction: column;
			align-items: baseline;
			& > * {
				margin: 4px 1px;
			}
		}
	}
}

</style>
