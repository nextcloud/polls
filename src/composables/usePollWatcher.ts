/**
 * SPDX-FileCopyrightText: 2025 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { watch, onBeforeUnmount, onMounted } from 'vue'
import { usePollStore } from '../stores/poll'
import { generateUrl } from '@nextcloud/router'

// eslint-disable-next-line import/default
import PollWatcherWorker from '../workers/pollWatcher.worker?worker'
import { Logger } from '../helpers'

import { useCommentsStore } from '../stores/comments'
import { useOptionsStore } from '../stores/options'
import { usePollsStore } from '../stores/polls'
import { useSessionStore } from '../stores/session'
import { useVotesStore } from '../stores/votes'
import { useSharesStore } from '../stores/shares'

import type {
	WatcherMode,
	WatcherProps,
	WatcherData,
	WorkerResponse,
} from './usePollWatcher.types'

import type { Watcher } from '../stores/session.types'

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
	const votesStore = useVotesStore()
	const optionsStore = useOptionsStore()
	const commentsStore = useCommentsStore()
	const sharesStore = useSharesStore()

	const baseUrl = generateUrl('apps/polls/')

	let worker: Worker | null = null

	/**
	 * Starts a new Web Worker that watches for updates
	 *
	 * @param pollId - ID of the currently active poll
	 * @param mode - polling mode (e.g. longPolling, periodicPolling, noPolling)
	 */
	const startWorker = (pollId: number | null | undefined, mode: WatcherMode) => {
		// if a worker is already running, terminate it first
		if (worker) {
			worker.terminate()
			worker = null
		}

		if (sessionStore.appSettings.updateType === 'noPolling') {
			return
		}

		worker = new PollWatcherWorker()

		// Pass context to worker
		worker.postMessage({
			pollId,
			mode,
			interval,
			baseUrl,
			token: sessionStore.token,
			watcherId: sessionStore.watcher.id,
			lastUpdate: sessionStore.watcher.lastUpdate,
		} satisfies WatcherProps)

		// Handle messages from worker
		worker.onmessage = (e: MessageEvent<WorkerResponse>) => {
			const { type, message, updates, status, mode, lastUpdate, params } =
				e.data

			sessionStore.watcher = <Watcher>{
				...sessionStore.watcher,
				mode,
				status,
				interval,
				lastUpdate: lastUpdate ?? sessionStore.watcher.lastUpdate,
				lastMessage: message ?? sessionStore.watcher.lastMessage,
			}

			switch (type) {
				case 'info':
					Logger.info(`[PollWatcher] ${message}`, { params })
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
					if (status === 'modeChanged') {
						sessionStore.load()
					}
					if (message) Logger.info(`[PollWatcher] ${message}`, { params })
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
				lastMessage: 'Watcher stopped.',
				lastUpdate: Math.floor(Date.now() / 1000),
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
		updates: WatcherData[],
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

		tasks.forEach((task: string) => {
			switch (task) {
				case 'shares':
					sharesStore.load()
					break
				case 'polls':
					pollStore.load()
					pollsStore.load()
					break
				case 'votes':
					votesStore.load()
					optionsStore.load()
					break
				case 'options':
					optionsStore.load()
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
	const handleWatcherUpdates = (updates: WatcherData[]) => {
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
		([pollIdNew, modeNew], [pollIdOld, modeOld]) => {
			Logger.debug('[PollWatcher] PollWatcher worker restarted:', {
				pollId: `${pollIdOld} → ${pollIdNew}`,
				mode: `${modeOld} → ${modeNew}`,
			})
			if (sessionStore.appSettings.updateType !== 'noPolling') {
				startWorker(pollIdNew, modeNew)
			}
		},
		{ immediate: true },
	)
}
