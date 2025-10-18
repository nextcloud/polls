<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { Logger } from '../../../helpers/modules/logger'
import { t } from '@nextcloud/l10n'

import { AdminAPI } from '../../../Api'
import { onMounted, ref } from 'vue'
import type { Job } from '../../../Api/modules/api.types'
import { NcActionButton, NcListItem } from '@nextcloud/vue'
import { DateTime } from 'luxon'
import PlayIcon from 'vue-material-design-icons/Play.vue'
import NotSupportedIcon from 'vue-material-design-icons/MinusCircleOutline.vue'
import JobExecutedIcon from 'vue-material-design-icons/CheckCircleOutline.vue'

const jobsList = ref<null | Job[]>(null)
const executedJobs = ref(new Set<string>())

async function loadJobs() {
	try {
		const response = await AdminAPI.getJobsList()
		jobsList.value = response.data.jobs
	} catch (error) {
		jobsList.value = null
		Logger.error('Error on fetching jobs list', { error })
	}
}
/**
 * fetch the jobs list on component mount
 */
onMounted(() => {
	loadJobs()
})

type LastRunInfo = {
	text: string
	title: string
}

type Description = {
	name: string
	description: string
}
const jobsDescription: Record<string, Description> = {
	'OCA\\Polls\\Cron\\AutoReminderCron': {
		name: 'Autoreminder',
		description: t('polls', 'Sends out pending automatic reminders.'),
	},
	'OCA\\Polls\\Cron\\JanitorCron': {
		name: 'Janitor',
		description: t(
			'polls',
			'Cleans up old or irrelevant data from the system to maintain optimal performance and organization.',
		),
	},
	'OCA\\Polls\\Cron\\NotificationCron': {
		name: 'Notification',
		description: t(
			'polls',
			'Sends out pending notifications about poll updates.',
		),
	},
}

function getDescription(job: Job): string {
	if (job.className in jobsDescription) {
		return jobsDescription[job.className].description
	}
	return t('polls', 'Description is missing.')
}

function getLastRun(job: Job): LastRunInfo {
	return job.lastRun === null
		? {
				text: t('polls', 'Not run yet'),
				title: t('polls', 'This job has never been run yet.'),
			}
		: {
				text: DateTime.fromSeconds(job.lastRun).toRelative() as string,
				title: DateTime.fromSeconds(job.lastRun).toLocaleString(
					DateTime.DATETIME_SHORT,
				),
			}
}

const jobHints: Record<string, string> = {
	line_one: t(
		'polls',
		'Please understand, that the jobs were defined as asynchronous jobs by intention.',
	),
	line_two: t(
		'polls',
		'Only use them, if it is absolutely neccessary (i.e. your cron does not work properly) or for testing.',
	),
	line_three: t(
		'polls',
		'Starting the jobs does not mean, that the rules for these actions are overridden.',
	),
	line_four: t(
		'polls',
		'The time information just displays the last regular cron job run, not the manually initiated runs.',
	),
	line_five: t(
		'polls',
		'Each job can only be run once. If you want to rerun them, you have to refresh the page.',
	),
	line_six: t('polls', 'If you want to see the result, please check the logs.'),
}

async function runJob(job: Job) {
	try {
		executedJobs.value.add(job.id)
		await AdminAPI.runJob(job)
	} catch (error) {
		Logger.error('Error on executing job', { error, job })
	}
}
</script>

<template>
	<div class="user_settings">
		<div class="job_hints">
			<p>
				{{ jobHints.line_one }}
				{{ jobHints.line_two }}
				{{ jobHints.line_three }}
				{{ jobHints.line_four }}
			</p>
			<p>
				{{ jobHints.line_five }}
				{{ jobHints.line_six }}
			</p>
		</div>
		<ul class="jobs_list">
			<NcListItem
				v-for="job in jobsList"
				:key="job.id"
				:name="job.name"
				:details="getLastRun(job).text"
				:title="getLastRun(job).title"
				:active="!executedJobs.has(job.id) && job.manuallyRunnable"
				force-display-actions>
				<template #subname>
					{{ getDescription(job) }}
				</template>
				<template #actions>
					<NcActionButton
						close-after-click
						:disabled="executedJobs.has(job.id) || !job.manuallyRunnable"
						:name="t('polls', 'Request Execution')"
						:aria-label="t('polls', 'Request job execution')"
						@click="runJob(job)">
						<template #icon>
							<JobExecutedIcon
								v-if="executedJobs.has(job.id)"
								:title="
									t('polls', '{jobName} has been executed.', {
										jobName: job.name,
									})
								" />
							<PlayIcon
								v-else-if="job.manuallyRunnable"
								:title="
									t('polls', 'Start {jobName} manually.', {
										jobName: job.name,
									})
								" />
							<NotSupportedIcon
								v-else
								:title="
									t(
										'polls',
										'{jobName} does not support manually run.',
										{ jobName: job.name },
									)
								" />
						</template>
					</NcActionButton>
				</template>
			</NcListItem>
		</ul>
	</div>
</template>

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
