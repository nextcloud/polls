<!--
  - @copyright Copyright (c) 2021 René Gieling <github@dartcafe.de>
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
	<div class="activity-item">
		<div class="activity-item__content">
			<span class="activity-item__date">{{ dateActivityRelative }}</span>
			<RichText :text="message.subject" :arguments="message.parameters" />
		</div>
	</div>
</template>

<script>
import moment from '@nextcloud/moment'
import { RichText } from '@nextcloud/vue-richtext'
import { NcUserBubble } from '@nextcloud/vue'
import SimpleLink from '../../helpers/SimpleLink.js'
import GuestBubble from '../../helpers/GuestBubble.js'

export default {
	name: 'ActivityItem',

	components: {
		RichText,
	},

	props: {
		activity: {
			type: Object,
			default: null,
		},
	},

	computed: {
		dateActivityRelative() {
			return moment(this.activity.datetime).fromNow()
		},

		message() {
			const subject = this.activity.subject_rich[0]
			const parameters = JSON.parse(JSON.stringify(this.activity.subject_rich[1]))
			if (parameters.after && typeof parameters.after.id === 'string' && parameters.after.id.startsWith('dt:')) {
				const dateTime = parameters.after.id.slice(3)
				parameters.after.name = moment(dateTime).format('L LTS')
			}

			Object.keys(parameters).forEach(function(key, index) {
				const { type } = parameters[key]
				switch (type) {
				case 'highlight':
					parameters[key] = {
						component: SimpleLink,
						props: {
							href: parameters[key].link,
							name: parameters[key].name,
						},
					}
					break
				case 'user':
					parameters[key] = {
						component: NcUserBubble,
						props: {
							user: parameters[key].id,
							displayName: parameters[key].name,
						},
					}
					break
				case 'guest':
					parameters[key] = {
						component: GuestBubble,
						props: {
							user: parameters[key].id,
							displayName: parameters[key].name,
						},
					}
					break
				default:
					parameters[key] = `{${key}}`
				}

			})

			return {
				subject, parameters,
			}
		},
	},
}
</script>

<style lang="scss">
	.activity-item {
		display: flex;
		align-items: start;
		margin-bottom: 24px;
	}

	.activity-item__date {
		opacity: 0.5;
		font-size: 0.8em;
		text-align: right;
		&::before {
			content: ' ~ '
		}
	}

	.activity-item__content {
		margin-left: 8px;
		flex: 1 1;
		padding-top: 2px;
	}
</style>
