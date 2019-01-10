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
	<div>
		<div class="wrapper group-master">
			<div class="wrapper group-1">
				<div :class="iconClass" />
				<router-link class="wrapper group-1-1" :to="{name: 'vote', params: {hash: poll.event.hash }}">
					<div class="flex-column name">
						{{ poll.event.title }}
					</div>
					<div class="flex-column description">
						{{ poll.event.description }}
					</div>
				</router-link>
				<div class="flex-column actions">
					<div class="icon-more popupmenu" />
					<div class="popovermenu bubble menu hidden">
						<ul>
							<li>
								<a class="menuitem alt-tooltip copy-link has-tooltip action permanent" href="#">
									<span class="icon-clippy" />
									<span> {{ t('polls', 'Copy Link') }} </span>
								</a>
							</li>
							<li>
								<a class="menuitem alt-tooltip delete-poll action
									permanent" href="#"
								>
									<span class="icon-delete" />
									<span> {{ t('polls', 'Delete poll') }} </span>
								</a>
							</li>
							<li>
								<a class="menuitem action permanent" href="#">
									<span class="icon-rename" />
									<span>{{ t('polls', 'Edit poll') }} </span>
								</a>
							</li>
						</ul>
					</div>
				</div>
			</div>
			<div class="wrapper group-2">
				<div class="flex-column owner">
					<user-div :user-id="poll.event.owner" :display-name="poll.event.ownerDisplayName" />
				</div>
				<div class="wrapper group-2-1">
					<div class="flex-column access">
						{{ accessType }}
					</div>
					<div class="flex-column created ">
						{{ poll.event.created }}
					</div>
				</div>
				<div class="wrapper group-2-2">
					<div class="flex-column expiry">
						{{ poll.event.expirationDate }}
					</div>
					<div class="flex-column participants">
						<div class="symbol alt-tooltip partic_voted icon-voted" />
						<div class="symbol alt-tooltip partic_commented icon-comment-yes" />
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
export default {
	props: {
		poll: {
			type: Object,
			default: undefined
		}
	},
	computed: {
		iconClass() {
			return 'thumbnail ' + this.poll.event.type + (this.poll.event.expired ? ' expired' : '')
		},

		accessType() {
			if (this.poll.event.access === 'public') {
				return t('polls', 'Public access')
			} else if (this.poll.event.access === 'select') {
				return t('polls', 'Only shared')
			} else if (this.poll.event.access === 'registered') {
				return t('polls','Registered users only')
			} else if (this.poll.event.access === 'hidden') {
				return t('polls','Hidden poll')
			} else {
				return ''
			}
		}
	}
}

</script>
<style lang="scss">
.thumbnail {
	width: 44px;
	height: 44px;
	padding-right: 4px;
	background-color: var(--color-primary-text-dark);
	&.datePoll {
		mask-image: var(--icon-calendar-000) no-repeat 50% 50%;
		-webkit-mask: var(--icon-calendar-000) no-repeat 50% 50%;
		mask-size: 32px;
	}
	&.textPoll {
		mask-image: var(--icon-organization-000) no-repeat 50% 50%;
		-webkit-mask: var(--icon-organization-000) no-repeat 50% 50%;
		mask-size: 32px;
	}
	&.expired {
		background-color: var(--color-error);
	}
}

</style>
