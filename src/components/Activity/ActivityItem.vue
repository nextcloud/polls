<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { computed } from 'vue'
import { DateTime } from 'luxon'

import NcUserBubble from '@nextcloud/vue/components/NcUserBubble'
import NcRichText from '@nextcloud/vue/components/NcRichText'

import { GuestBubble, SimpleLink } from '../../helpers'

const props = defineProps({
	activity: {
		type: Object,
		default: null,
	},
})

const dateActivityRelative = computed(() =>
	DateTime.fromISO(props.activity.datetime).toRelative(),
)

const message = computed(() => {
	const subject = props.activity.subject_rich[0]
	const parameters = JSON.parse(JSON.stringify(props.activity.subject_rich[1]))
	if (
		parameters.after
		&& typeof parameters.after.id === 'string'
		&& parameters.after.id.startsWith('dt:')
	) {
		const dateTime = parameters.after.id.slice(3)
		parameters.after.name = DateTime.fromISO(dateTime).toLocaleString(
			DateTime.DATETIME_SHORT,
		)
	}

	Object.keys(parameters).forEach(function (key) {
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
			case 'user':
				parameters[key] = {
					component: NcUserBubble,
					props: {
						user: parameters[key].id,
						displayName: parameters[key].name,
					},
				}
				break
			case 'circle':
				parameters[key] = {
					component: SimpleLink,
					props: {
						href: parameters[key].link,
						name: parameters[key].name,
					},
				}
				break
			case 'addressbook-contact':
			case 'email':
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
		subject,
		parameters,
	}
})
</script>

<template>
	<div class="activity-item">
		<div class="activity-item__content">
			<span class="activity-item__date">{{ dateActivityRelative }}</span>
			<NcRichText :text="message.subject" :arguments="message.parameters" />
		</div>
	</div>
</template>

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
		content: ' ~ ';
	}
}

.activity-item__content {
	margin-inline-start: 8px;
	flex: 1 1;
	padding-top: 2px;
}
</style>
