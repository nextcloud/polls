/**
 * SPDX-FileCopyrightText: 2024 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { AxiosResponse } from 'axios'
import { httpInstance, createCancelTokenHandler } from './HttpApi'
import type { Job, JobsList } from './api.types'

const adminJobs = {
	getJobsList(): Promise<AxiosResponse<{ jobs: JobsList }>> {
		return httpInstance.request({
			method: 'GET',
			url: 'administration/jobs',
			cancelToken:
				cancelTokenHandlerObject[
					this.getJobsList.name
				].handleRequestCancellation().token,
		})
	},

	runJob(job: Job): Promise<AxiosResponse<{ job: Job }>> {
		return httpInstance.request({
			method: 'POST',
			url: 'administration/job/run',
			data: {
				job: job.className,
			},

			cancelToken:
				cancelTokenHandlerObject[
					this.runJob.name
				].handleRequestCancellation().token,
		})
	},
}

const cancelTokenHandlerObject = createCancelTokenHandler(adminJobs)

export default adminJobs
