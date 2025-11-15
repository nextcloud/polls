<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'

import { DateTime, Interval } from 'luxon'

// eslint-disable-next-line import/named
import { Sheet, WorkBook, utils as xlsxUtils, write as xlsxWrite } from 'xlsx'
import DOMPurify from 'dompurify'
import { saveAs } from 'file-saver'
import { t } from '@nextcloud/l10n'
import { showError } from '@nextcloud/dialogs'

import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'

import ExcelIcon from 'vue-material-design-icons/MicrosoftExcel.vue'
import FileTableIcon from 'vue-material-design-icons/FileTableOutline.vue'
import CsvIcon from 'vue-material-design-icons/FileDelimitedOutline.vue'
import XmlIcon from 'vue-material-design-icons/Xml.vue'
import ExportIcon from 'vue-material-design-icons/FileDownloadOutline.vue'

import { ApiEmailAdressList, PollsAPI } from '../../Api'
import { usePollStore } from '../../stores/poll'
import { useVotesStore } from '../../stores/votes'
import { useOptionsStore } from '../../stores/options'

import type { AxiosError } from '@nextcloud/axios'
import type { Option } from '../../stores/options.types'
import type { Answer } from '../../stores/votes.types'
import { Logger } from '@/helpers/modules/logger'

type ArrayStyle = 'symbols' | 'raw' | 'generic'
type ExportFormat = 'html' | 'xlsx' | 'ods' | 'csv'

const route = useRoute()
const pollStore = usePollStore()
const votesStore = useVotesStore()
const optionsStore = useOptionsStore()

const regex = /[:\\/?*[\]]/g

const workBook = ref<null | WorkBook>(null)
const sheetData = ref<Sheet>([])
const emailAddresses = ref<ApiEmailAdressList[]>([])
const sheetName = computed(() =>
	pollStore.configuration.title.replaceAll(regex, '').slice(0, 31),
)

function s2ab(s: string) {
	const buf = new ArrayBuffer(s.length) // convert s to arrayBuffer
	const view = new Uint8Array(buf) // create uint8array as viewer
	for (let i = 0; i < s.length; i++) {
		view[i] = s.charCodeAt(i) & 0xff
	} // convert to octet
	return buf
}

/**
 *
 * @param answer
 */
function getAnswerTranslated(answer: Answer) {
	switch (answer) {
		case 'yes':
			return t('polls', 'Yes')
		case 'maybe':
			return t('polls', 'Maybe')
		default:
			return t('polls', 'No')
	}
}
/**
 *
 * @param exportFormat - export type
 */
async function exportFile(exportFormat: ExportFormat) {
	const participantsHeader = [t('polls', 'Participants')]
	const fromHeader = [t('polls', 'From')]
	const toHeader = [t('polls', 'To')]
	workBook.value = xlsxUtils.book_new()
	workBook.value.SheetNames.push(sheetName.value)
	sheetData.value = []

	if (['html', 'xlsx', 'ods'].includes(exportFormat)) {
		sheetData.value.push(
			[DOMPurify.sanitize(pollStore.configuration.title)],
			[DOMPurify.sanitize(pollStore.configuration.description)],
		)
	}

	if (pollStore.permissions.edit) {
		try {
			participantsHeader.push(t('polls', 'Email address'))
			fromHeader.push('')
			toHeader.push('')
			const response = await PollsAPI.getParticipantsEmailAddresses(
				route.params.id,
			)
			emailAddresses.value = response.data
		} catch (error) {
			if ((error as AxiosError).name === 'CanceledError') {
				return
			}
		}
	}

	if (pollStore.type === 'textPoll') {
		if (['html'].includes(exportFormat)) {
			sheetData.value.push([
				...participantsHeader,
				...optionsStore.options.map((item) => DOMPurify.sanitize(item.text)),
			])
		} else {
			sheetData.value.push([
				...participantsHeader,
				...optionsStore.options.map((item) => item.text),
			])
		}
	} else if (['csv'].includes(exportFormat)) {
		sheetData.value.push([
			...participantsHeader,
			...optionsStore.options.map((option) => getIntervalIso(option)),
		])
	} else if (['html'].includes(exportFormat)) {
		sheetData.value.push([
			...participantsHeader,
			...optionsStore.options.map((option) => getIntervalRaw(option)),
		])
	} else {
		sheetData.value.push([
			...fromHeader,
			...optionsStore.options.map((option) => getFromFormatted(option)),
		])
		sheetData.value.push([
			...toHeader,
			...optionsStore.options.map((option) => getToFormatted(option)),
		])
	}

	if (['html', 'ods', 'xlsx'].includes(exportFormat)) {
		addVotesArray('symbols')
	} else if (['csv'].includes(exportFormat)) {
		addVotesArray('raw')
	} else {
		addVotesArray('generic')
	}
	try {
		const workBookOutput = xlsxWrite(workBook.value, {
			bookType: exportFormat,
			type: 'binary',
		})
		saveAs(
			new Blob([s2ab(workBookOutput)], { type: 'application/octet-stream' }),
			`pollStore.${exportFormat}`,
		)
	} catch (error) {
		Logger.error('Error exporting file.', { error })
		showError(t('polls', 'Error exporting file.'))
	}
}

/**
 * Get the interval in ISO format (slightly modified)
 * @param option - option
 */
function getIntervalIso(option: Option): string {
	return Interval.fromDateTimes(
		DateTime.fromSeconds(option.timestamp).toUTC(),
		DateTime.fromSeconds(option.timestamp)
			.plus({ seconds: option.duration })
			.toUTC(),
	)
		.toISO()
		.replace('/', ' - ')
}

/**
 * Get the interval in local format
 * @param option - option
 */
function getIntervalRaw(option: Option): string {
	return Interval.fromDateTimes(
		DateTime.fromSeconds(option.timestamp),
		DateTime.fromSeconds(option.timestamp).plus({ seconds: option.duration }),
	).toLocaleString(DateTime.DATETIME_MED_WITH_WEEKDAY)
}

/**
 * Get the start date in local format
 * @param option - option
 */
function getFromFormatted(option: Option): string {
	return DateTime.fromSeconds(option.timestamp).toLocaleString(
		DateTime.DATETIME_MED_WITH_WEEKDAY,
	)
}

/**
 * Get the end date in local format
 * @param option - option
 */
function getToFormatted(option: Option): string {
	return DateTime.fromSeconds(option.timestamp)
		.plus({ seconds: option.duration })
		.toLocaleString(DateTime.DATETIME_MED_WITH_WEEKDAY)
}
/**
 *
 * @param style - style
 */
function addVotesArray(style: ArrayStyle) {
	if (!workBook.value) {
		return
	}

	votesStore.participants.forEach((participant) => {
		const votesLine = [participant.displayName]
		try {
			if (pollStore.permissions.edit) {
				votesLine.push(
					emailAddresses.value.find(
						(item) => item.displayName === participant.displayName,
					)?.emailAddress ?? '',
				)
			}

			optionsStore.options.forEach((option) => {
				if (style === 'symbols') {
					votesLine.push(
						votesStore.getVote({
							user: participant,
							option,
						}).answerSymbol ?? '❌',
					)
				} else if (style === 'raw') {
					votesLine.push(
						votesStore.getVote({
							user: participant,
							option,
						}).answer,
					)
				} else {
					votesLine.push(
						getAnswerTranslated(
							votesStore.getVote({
								user: participant,
								option,
							}).answer,
						),
					)
				}
			})

			sheetData.value.push(votesLine)
		} catch (error) {
			// just skip this participant
		}
	})

	const workSheet = xlsxUtils.aoa_to_sheet(sheetData.value as unknown[][])
	workBook.value.Sheets[sheetName.value] = workSheet
}
</script>

<template>
	<NcActions>
		<template #icon>
			<ExportIcon />
		</template>
		<NcActionButton
			close-after-click
			:name="t('polls', 'Download Excel spreadsheet')"
			:aria-label="t('polls', 'Download Excel spreadsheet')"
			@click="exportFile('xlsx')">
			<template #icon>
				<ExcelIcon />
			</template>
		</NcActionButton>

		<NcActionButton
			close-after-click
			:name="t('polls', 'Download Open Document spreadsheet')"
			:aria-label="t('polls', 'Download Open Document spreadsheet')"
			@click="exportFile('ods')">
			<template #icon>
				<FileTableIcon />
			</template>
		</NcActionButton>

		<NcActionButton
			close-after-click
			:name="t('polls', 'Download CSV file')"
			::aria-label="t('polls', 'Download CSV file')"
			@click="exportFile('csv')">
			<template #icon>
				<CsvIcon />
			</template>
		</NcActionButton>

		<NcActionButton
			close-after-click
			:name="t('polls', 'Download HTML file')"
			:aria-label="t('polls', 'Download HTML file')"
			@click="exportFile('html')">
			<template #icon>
				<XmlIcon />
			</template>
		</NcActionButton>
	</NcActions>
</template>
