/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { watch, onBeforeUnmount } from 'vue'
import { useSessionStore, Watcher } from '../stores/session'
import { usePollStore } from '../stores/poll'
import { generateUrl } from '@nextcloud/router'
// eslint-disable-next-line import/default
import PollWatcherWorker from '../workers/pollWatcher.worker?worker'
import { Logger } from '../helpers/index.ts'
import type { WatcherResponse } from './usePollWatcher.types'
import { forEach } from 'lodash'
import { usePollsStore } from '../stores/polls.ts'
import { useOptionsStore } from '../stores/options.ts'
import { useCommentsStore } from '../stores/comments.ts'

/**
 * poll watcher to keep polls collection and the current poll
 * up-to-date as soon as possible
 * Simulates real-time updates using long polling or interval polling
 *
 * @param interval - polling interval in milliseconds (default: 30000)
 */
export const usePollWatcher = (interval = 30000) => {
	const sessionStore = useSessionStore()
	const pollStore = usePollStore()
	const pollsStore = usePollsStore()
	const votesStore = usePollStore()
	const optionsStore = useOptionsStore()
	const commentsStore = useCommentsStore()

	const baseUrl = generateUrl('apps/polls/')

	let worker: Worker | null = null

	/**
	 * Starts a new Web Worker that watches for updates
	 *
	 * @param pollId - ID of the currently active poll
	 * @param updateType - polling mode (e.g. longPolling, periodicPolling, noPolling)
	 */
	const startWorker = (pollId: number | null | undefined, updateType: string) => {
		// if a worker is already running, terminate it first
		if (worker) {
			worker.terminate()
			worker = null
		}

		worker = new PollWatcherWorker()

		// Pass context to worker
		worker.postMessage({
			pollId,
			updateType,
			interval,
			baseUrl,
			token: sessionStore.token,
			watcherId: sessionStore.watcher.id,
		})

		// Handle messages from worker
		worker.onmessage = (e) => {
			const { type, message, updates, status, mode } = e.data

			sessionStore.watcher = <Watcher>{
				...sessionStore.watcher,
				mode,
				status,
				interval,
				lastUpdated: Date.now(),
				lastMessage: message ?? sessionStore.watcher.lastMessage,
			}

			switch (type) {
				case 'info':
					Logger.info(`[PollWatcher] ${message}`)
					break
				case 'debug':
					Logger.debug(`[PollWatcher] ${message}`)
					break
				case 'error':
				case 'fatal':
					Logger.error(`[PollWatcher] ${message}`)
					break
				case 'update':
					Logger.info(`[PollWatcher] ${message}`)
					if (Array.isArray(updates)) {
						handleWatcherUpdates(updates)
					}
					break
				case 'status':
					if (message) Logger.info(`[PollWatcher] ${message}`)
					break
				default:
					Logger.warn('[PollWatcher] Unknown message type:', { type })
			}
		}
	}

	/**
	 * Terminate the current worker
	 */
	const stopWorker = () => {
		if (worker) {
			worker.terminate()
			worker = null
			sessionStore.watcher = <Watcher>{
				...sessionStore.watcher,
				status: 'stopped',
				lastUpdated: Date.now(),
				lastMessage: 'Watcher stopped.',
			}
			Logger.info('[PollWatcher] Worker stopped.')
		}
	}

	/**
	 * Determines which store modules to update based on incoming WatcherResponse objects
	 *
	 * @param updates - list of update events from the server
	 * @param currentPollId - current poll ID to distinguish between own and external changes
	 * @return list of update types to apply
	 */
	const getTasksFromUpdates = (
		updates: WatcherResponse[],
		currentPollId: number,
	): string[] => {
		// Use a Set to prevent duplicates
		const tasks = new Set<string>()

		for (const update of updates) {
			if (update.pollId === currentPollId) {
				tasks.add(update.table)
			} else if (update.table === 'polls') {
				if (update.pollId === currentPollId) {
					tasks.add('poll')
				}
				tasks.add('polls')
			}
		}

		// Return the Set as array
		return Array.from(tasks)
	}

	/**
	 * Handles the actions based on the tsaks received from the worker
	 *
	 * @param tasks - list of tasks to handle
	 */
	const handleWatcherTasks = (tasks: string[]) => {
		Logger.info('[PollWatcher] Tasks to handle:', { tasks })

		forEach(tasks, (task) => {
			switch (task) {
				case 'poll':
					pollStore.load()
					break
				case 'polls':
					pollsStore.load()
					break
				case 'votes':
					votesStore.load()
					optionsStore.load()
					break
				case 'options':
					pollStore.load()
					break
				case 'comments':
					commentsStore.load()
					break
			}
		})
	}

	/**
	 * Dispatches updates to the relevant store modules based on change type.
	 *
	 * @param updates - update information from the worker
	 */
	const handleWatcherUpdates = (updates: WatcherResponse[]) => {
		const tasks = getTasksFromUpdates(updates, pollStore.id)
		Logger.info('[PollWatcher] Updates received:', { updates })
		handleWatcherTasks(tasks)
	}

	/**
	 * Handles visibility changes for the browser tab.
	 * Stops the worker when the tab is hidden, restarts it when visible again.
	 */
	const handleVisibilityChange = () => {
		if (document.visibilityState === 'visible') {
			Logger.info('[PollWatcher] Window visible → restarting worker')
			startWorker(pollStore.id, sessionStore.appSettings.updateType)
		} else {
			Logger.info('[PollWatcher] Window hidden → stopping worker')
			stopWorker()
		}
	}

	/**
	 * Initialize visibility handling and start worker if visible.
	 */
	onMounted(() => {
		document.addEventListener('visibilitychange', handleVisibilityChange)

		if (document.visibilityState === 'visible') {
			startWorker(pollStore.id, sessionStore.appSettings.updateType)
		}
	})

	onBeforeUnmount(() => {
		document.removeEventListener('visibilitychange', handleVisibilityChange)
		stopWorker()
	})

	/**
	 * Reactively restart the worker whenever pollId or updateType changes.
	 */
	watch(
		[() => pollStore.id, () => sessionStore.appSettings.updateType],
		([pollId, updateType]) => {
			startWorker(pollId, updateType)
		},
		{ immediate: true },
	)
}
