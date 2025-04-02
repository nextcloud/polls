<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="user_settings">
		<div class="job_hints">
			<p>
				{{ t('polls', 'Please understand, that the jobs were defined as asynchronous jobs by intention.') }}
				{{ t('polls', 'Only use them, if it is absolutely neccessary (i.error. your cron does not work properly) or for testing.') }}
				{{ t('polls', 'Starting the jobs does not mean, that the rules for these actions are overridden.') }}
			</p>
			<p>
				{{ t('polls', 'Each job can only be run once. If you want to rerun them, you have to refresh the page.') }}
				{{ t('polls', 'If you want to see the result, please check the logs.') }}
			</p>
		</div>
		<div class="job_buttons_section">
			<NcButton variant="primary"
				:aria-label="autoreminder.text"
				:disabled="autoreminder.disabled"
				@click="runAutoReminderJob()">
				{{ autoreminder.text }}
			</NcButton>

			<NcButton variant="primary"
				:aria-label="janitor.text"
				:disabled="janitor.disabled"
				@click="runJanitorJob()">
				{{ janitor.text }}
			</NcButton>

			<NcButton variant="primary"
				:aria-label="notification.text"
				:disabled="notification.disabled"
				@click="runNotificationJob()">
				{{ notification.text }}
			</NcButton>
		</div>
	</div>
</template>

<script>

import { NcButton } from '@nextcloud/vue'
import { AdminAPI } from '../../../Api/index.js'
import { Logger } from '../../../helpers/index.js'

export default {
	name: 'AdminJobs',

	components: {
		NcButton,
	},

	data() {
		return {
			autoreminder: {
				text: t('polls', 'Run autoreminder'),
				disabled: false,
			},
			janitor: {
				text: t('polls', 'Run janitor'),
				disabled: false,
			},
			notification: {
				text: t('polls', 'Run notification'),
				disabled: false,
			},
		}
	},
	methods: {
		async runAutoReminderJob() {
			try {
				AdminAPI.runAutoReminder()
				this.autoreminder.disabled = true
				this.autoreminder.text = t('polls', 'Autoreminder started')
			} catch (error) {
				this.autoreminder.text = t('polls', 'Autoreminder failed')
				Logger.error('Error on executing autoreminder job', { error })
			} finally {
				this.autoreminder.disabled = true
			}
		},
		async runJanitorJob() {
			try {
				AdminAPI.runJanitor()
				this.janitor.text = t('polls', 'Janitor started')
			} catch (error) {
				this.janitor.text = t('polls', 'Janitor failed')
				Logger.error('Error on executing janitor job', { error })
			} finally {
				this.janitor.disabled = true
			}
		},
		async runNotificationJob() {
			try {
				AdminAPI.runNotification()
				this.notification.text = t('polls', 'Notification started')
			} catch (error) {
				this.notification.text = t('polls', 'Notification failed')
				Logger.error('Error on executing notification job', { error })
			} finally {
				this.notification.disabled = true
			}
		},
	}
}
</script>

<style lang="scss">
.user_settings {
	.job_buttons_section {
		display: flex;
		flex-wrap: wrap;
		margin-top: 20px;
		gap: 12px;
	}
	.job_hints p {
		margin-bottom: 0.5em;
	}
}
</style>
