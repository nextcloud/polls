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
	<div id="app-content">
		<controls>
			<router-link :to="{ name: 'create'}" class="button">
				<span class="symbol icon-add" />
				<span class="hidden-visually">
					{{ t('polls', 'New') }}
				</span>
			</router-link>
		</controls>

		<div v-if="noPolls" class="">
			<div class="icon-polls" />
			<h2> {{ t('No existing polls.') }} </h2>
			<router-link :to="{ name: 'create'}" class="button new">
				<span>{{ t('polls', 'Click here to add a poll') }}</span>
			</router-link>
		</div>

		<transition-group
			v-if="!noPolls"
			name="list"
			tag="div"
			class="poll-list table main-container has-controls"
		>
			<poll-list-header
				key="0"
				class="table-row table-header"
			/>
			<li
				is="poll-list-item"
				v-for="(poll, index) in polls"
				:key="poll.id"
				:poll="poll"
				class="table-row table-body"
				@deletePoll="removePoll(index, poll.event)"
			/>
		</transition-group>
		<loading-overlay v-if="loading" />
		<modal-dialog />
	</div>
</template>

<script>
// import moment from 'moment'
// import lodash from 'lodash'
import pollListItem from '../components/pollListItem'
import pollListHeader from '../components/pollListHeader'

export default {
	name: 'List',

	components: {
		pollListItem,
		pollListHeader
	},

	data() {
		return {
			noPolls: false,
			loading: true,
			polls: []
		}
	},

	created() {
		this.indexPage = OC.generateUrl('apps/polls/')
		this.loadPolls()
	},

	methods: {
		loadPolls() {
			this.loading = true
			this.$http.get(OC.generateUrl('apps/polls/get/polls'))
				.then((response) => {
					this.polls = response.data
					this.loading = false
				}, (error) => {
					/* eslint-disable-next-line no-console */
					console.log(error.response)
					this.loading = false
				})
		},

		removePoll: function(index, event) {
			const params = {
				title: t('polls', 'Delete poll'),
				text: t('polls', 'Do you want to delete "%n"?', 1, event.title),
				buttonHideText: t('polls','No, keep poll.'),
				buttonConfirmText: t('polls','Yes, delete poll.'),
				onConfirm: () => {
					// this.deletePoll(index, event)
					this.$http.post(OC.generateUrl('apps/polls/remove/poll'), event)
						.then((response) => {
							this.polls.splice(index, 1)
							OC.Notification.showTemporary(t('polls', 'Poll "%n" deleted', 1, event.title))
						}, (error) => {
							OC.Notification.showTemporary(t('polls', 'Error while deleting Poll "%n"', 1, event.title))
							/* eslint-disable-next-line no-console */
							console.log(error.response)
						}
						)
				}
			}
			this.$modal.show(params)
		}

	}
}
</script>

<style lang="scss">
$row-padding: 15px;
$table-padding: 4px;

$date-width: 120px;
$participants-width: 95px;
$group-2-2-width: max($date-width, $participants-width);

$owner-width: 140px;
$access-width: 44px;
$group-2-1-width: max($access-width, $date-width);
$group-2-width: $owner-width + $group-2-1-width + $group-2-2-width;

$action-width: 44px;
$thumbnail-width: 44px;
$thumbnail-icon-width: 32px;
$name-width: 150px;
$description-width: 150px;
$group-1-1-width: max($name-width, $description-width);
$group-1-width: $thumbnail-width + $group-1-1-width + $action-width;

$group-master-width: max($group-1-width, $group-2-width);

$mediabreak-1: ($group-1-width + $owner-width + $access-width + $date-width + $date-width + $participants-width + $row-padding * 2);
$mediabreak-2: ($group-1-width + $group-2-width + $row-padding * 2);
$mediabreak-3: $group-1-width + $owner-width + max($group-2-1-width, $group-2-2-width) + $row-padding *2 ;

.table {
	width: 100%;
}

#emptycontent {
	.icon-polls {
		background-color: black;
		-webkit-mask: url('./img/app.svg') no-repeat 50% 50%;
		mask: url('./img/app.svg') no-repeat 50% 50%;
	}
}

.poll-list {
	margin-top: 45px;
	display: flex;
	flex-direction: column;
	flex-grow: 1;
	flex-wrap: nowrap;
}

.table-row {
	display: flex;
	width: 100%;
	padding-left:  $row-padding;
	padding-right: $row-padding;

	line-height: 2em;
	transition: background-color 0.3s ease;
	background-color: var(--color-main-background);
	min-height: 4em;
	border-bottom: 1px solid var(--color-border);

	&.table-header {
		.name, .description {
			padding-left: ($thumbnail-width + $table-padding *2);
		}
		.owner {
			padding-left: 6px;
		}
	}

	&.table-body {
		&:hover, &:focus, &:active, &.mouseOver {
			transition: background-color 0.3s ease;
			background-color: var(--color-background-dark);
		}
		.flex-column.owner {
			display: flex;
			.avatardiv {
				margin-right: 4px;
			}
		}
		.icon-more {
			right: 14px;
			opacity: 0.3;
			cursor: pointer;
			height: 44px;
			width: 44px;
		}

		.symbol {
			padding: 2px;
		}

	}

	&.table-header {
		opacity: 0.5;
	}
}

.wrapper {
	display: flex;
	align-items: center;
	position: relative;
	div {
	}
}

.flex-column {
	padding: 0 $table-padding;
	overflow: hidden;
	white-space: nowrap;
	text-overflow: ellipsis;
	align-items: center;
	min-height: 16px;
}

.name {
	width: $name-width;
}

.description {
	width: $description-width;
	color: var(--color-text-maxcontrast);
	}

.owner {
	width: $owner-width;
}

.access {
	width: $access-width;
}

.created {
	width: $date-width;
}

.expiry {
	width: $date-width;
	&.expired {
		color: red;
	}
}

.participants{
	width: $participants-width;
	div {
		&.partic_voted {
			&.icon-partic_yes {
				background-image: var(--icon-yes);
			}
			&.icon-partic_no {
				background-image: var(--icon-no);
			}
		}

		&.partic_commented {
			&.icon-commented_yes {
				background-image: var(--icon-comment-yes);
			}
			&.icon-commented_no {
				background-image: var(--icon-comment-no);
			}
		}
	}

}

.actions {
	width: $action-width;
	position: relative;
	overflow: initial;
}

.options, .participants {
	display: flex;
	flex-direction: row;
}

.group-1, .group-1-1, .group-master {
	flex-grow: 1;
}

.group-1-1 {
	flex-direction: column;
	width: $group-1-1-width;
	> div {
		width: 100%;
	}
}

@media all and (max-width: ($mediabreak-1) ) {
	.group-1 {
		width: $group-1-width;
	}
	.group-2-1, .group-2-2 {
		flex-direction: column;
	}

	.access, .created {
		width: $group-2-1-width;;
	}
	.expiry, .participants {
		width: $group-2-2-width;;
	}
}

@media all and (max-width: ($mediabreak-2) ) {
	.table-row {
		padding: 0;
	}

	.group-2-1 {
		display: none;
	}
}

@media all and (max-width: ($mediabreak-3) ) {
	.group-2 {
		display: none;
	}
}

</style>
