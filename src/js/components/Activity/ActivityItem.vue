<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="activity-item">
		<div class="activity-item__content">
			<span class="activity-item__date">{{ dateActivityRelative }}</span>
			<NcRichText :text="message.subject" :arguments="message.parameters" />
		</div>
	</div>
</template>

<script>
import moment from '@nextcloud/moment'
import { NcUserBubble, NcRichText } from '@nextcloud/vue'
import { GuestBubble, SimpleLink } from '../../helpers/index.js'

export default {
	name: 'ActivityItem',

	components: {
		NcRichText,
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
					parameters[key] = parameters[key].link
						? {
							component: SimpleLink,
							props: {
								href: parameters[key].link,
								name: parameters[key].name,
							},
						}
						: `${parameters[key].name}`
					break
				case 'circle':
					parameters[key] =  {
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
		text-align: end;
		&::before {
			content: ' ~ '
		}
	}

	.activity-item__content {
		margin-inline-start: 8px;
		flex: 1 1;
		padding-top: 2px;
	}
</style>
