<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
	import { ref, computed } from 'vue'
	import { useRoute } from 'vue-router'
	import { utils as xlsxUtils, write as xlsxWrite } from 'xlsx'
	import { saveAs } from 'file-saver'
	import DOMPurify from 'dompurify'
	import { t } from '@nextcloud/l10n'
	import { NcActions, NcActionButton } from '@nextcloud/vue'
	import { showError } from '@nextcloud/dialogs'

	import { usePollStore, PollType } from '../../stores/poll.ts'
	import { useVotesStore } from '../../stores/votes.ts'
	import { useOptionsStore } from '../../stores/options.ts'

	import { PollsAPI } from '../../Api/index.js'

	import ExcelIcon from 'vue-material-design-icons/MicrosoftExcel.vue'
	import FileTableIcon from 'vue-material-design-icons/FileTableOutline.vue'
	import CsvIcon from 'vue-material-design-icons/FileDelimited.vue'
	import XmlIcon from 'vue-material-design-icons/Xml.vue'
	import ExportIcon from 'vue-material-design-icons/FileDownloadOutline.vue'

	const route = useRoute()
	const pollStore = usePollStore()
	const votesStore = useVotesStore()
	const optionsStore = useOptionsStore()

	const regex = /[:\\/?*[\]]/g

	const workBook = ref(null)
	const sheetData = ref([])
	const emailAddresses = ref([])
	const sheetName = computed(() => pollStore.configuration.title.replaceAll(regex, '').slice(0, 31))

	/**
	 *
	 * @param s - string
	 */
	function s2ab(s) {
		const buf = new ArrayBuffer(s.length) // convert s to arrayBuffer
		const view = new Uint8Array(buf) // create uint8array as viewer
		for (let i = 0; i < s.length; i++) view[i] = s.charCodeAt(i) & 0xFF // convert to octet
		return buf
	}

	/**
	 *
	 * @param exportType - export type
	 */
	async function exportFile(exportType) {
		const participantsHeader = [t('polls', 'Participants')]
		const fromHeader = [t('polls', 'From')]
		const toHeader = [t('polls', 'To')]
		workBook.value = xlsxUtils.book_new()
		workBook.value.SheetNames.push(sheetName.value)
		sheetData.value = []

		if (['html', 'xlsx', 'ods'].includes(exportType)) {
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
				const response = await PollsAPI.getParticipantsEmailAddresses(route.params.id)
				emailAddresses.value = response.data
			} catch (error) {
				if (error.name === 'CanceledError') return
			}
		}

		if (pollStore.type === PollType.Text) {
			if (['html'].includes(exportType)) {
				sheetData.value.push([
					...participantsHeader,
					...optionsStore.list.map((item) => DOMPurify.sanitize(item.text)),
				])
			} else {
				sheetData.value.push([
					...participantsHeader,
					...optionsStore.list.map((item) => item.text),
				])
			}

		} else if (['csv'].includes(exportType)) {
			sheetData.value.push([
				...participantsHeader,
				...optionsStore.list.map((option) => optionsStore.explodeDates(option).iso),
			])

		} else if (['html'].includes(exportType)) {
			sheetData.value.push([
				...participantsHeader,
				...optionsStore.list.map((option) => optionsStore.explodeDates(option).raw),
			])

		} else {
			sheetData.value.push([
				...fromHeader,
				...optionsStore.list.map((option) => optionsStore.explodeDates(option).from.dateTime),
			])
			sheetData.value.push([
				...toHeader,
				...optionsStore.list.map((option) => optionsStore.explodeDates(option).to.dateTime),
			])
		}

		if (['html', 'ods', 'xlsx'].includes(exportType)) {
			addVotesArray('symbols')
		} else if (['csv'].includes(exportType)) {
			addVotesArray('raw')
		} else {
			addVotesArray('generic')
		}
		try {
			const workBookOutput = xlsxWrite(workBook.value, { bookType: exportType, type: 'binary' })
			saveAs(new Blob([s2ab(workBookOutput)], { type: 'application/octet-stream' }), `pollStore.${exportType}`)
		} catch (error) {
			console.error(error)
			showError(t('polls', 'Error exporting file.'))
		}
	}

	/**
	 *
	 * @param style - style
	 */
	function addVotesArray(style: 'symbols' | 'raw' | 'generic') {
		pollStore.participants.forEach((participant) => {
			const votesLine = [participant.displayName]
			try {
				if (pollStore.permissions.edit) {
					votesLine.push(emailAddresses.value.find((item) => item.displayName === participant.displayName).emailAddress)
				}

				optionsStore.list.forEach((option) => {
					if (style === 'symbols') {
						votesLine.push(votesStore.getVote({ userId: participant.id, option }).answerSymbol ?? '❌')
					} else if (style === 'raw') {
						votesLine.push(votesStore.getVote({ userId: participant.id, option }).answer)
					} else {
						votesLine.push(votesStore.getVote({ userId: participant.id, option }).answerTranslated ?? t('polls', 'No'))
					}
				})

				sheetData.value.push(votesLine)
			} catch (error) {
			// just skip this participant
			}
		})

		const workSheet = xlsxUtils.aoa_to_sheet(sheetData.value)
		workBook.value.Sheets[sheetName.value] = workSheet
	}
</script>

<template>
	<NcActions>
		<template #icon>
			<ExportIcon />
		</template>
		<NcActionButton close-after-click
			:name="t('polls', 'Download Excel spreadsheet')"
			:aria-label="t('polls', 'Download Excel spreadsheet')"
			@click="exportFile('xlsx')">
			<template #icon>
				<ExcelIcon />
			</template>
		</NcActionButton>

		<NcActionButton close-after-click
			:name="t('polls', 'Download Open Document spreadsheet')"
			:aria-label="t('polls', 'Download Open Document spreadsheet')"
			@click="exportFile('ods')">
			<template #icon>
				<FileTableIcon />
			</template>
		</NcActionButton>

		<NcActionButton close-after-click
			:name="t('polls', 'Download CSV file')"
			::aria-label="t('polls', 'Download CSV file')"
			@click="exportFile('csv')">
			<template #icon>
				<CsvIcon />
			</template>
		</NcActionButton>

		<NcActionButton close-after-click
			:name="t('polls', 'Download HTML file')"
			:aria-label="t('polls', 'Download HTML file')"
			@click="exportFile('html')">
			<template #icon>
				<XmlIcon />
			</template>
		</NcActionButton>
	</NcActions>
</template>
