<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

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

<script>
import { mapStores } from 'pinia'
import { saveAs } from 'file-saver'
import { utils as xlsxUtils, write as xlsxWrite } from 'xlsx'
import { NcActions, NcActionButton } from '@nextcloud/vue'
import ExcelIcon from 'vue-material-design-icons/MicrosoftExcel.vue'
import FileTableIcon from 'vue-material-design-icons/FileTableOutline.vue'
import CsvIcon from 'vue-material-design-icons/FileDelimited.vue'
import XmlIcon from 'vue-material-design-icons/Xml.vue'
import ExportIcon from 'vue-material-design-icons/FileDownloadOutline.vue'
import { PollsAPI } from '../../Api/index.js'
import DOMPurify from 'dompurify'
import { t } from '@nextcloud/l10n'
import { usePollStore } from '../../stores/poll.ts'
import { useVotesStore } from '../../stores/votes.ts'
import { useOptionsStore } from '../../stores/options.ts'


export default {
	name: 'ExportPoll',
	components: {
		NcActions,
		NcActionButton,
		CsvIcon,
		ExcelIcon,
		FileTableIcon,
		ExportIcon,
		XmlIcon,
	},

	data() {
		return {
			workBook: [],
			sheetData: [],
			emailAddresses: [],
		}
	},

	computed: {
		...mapStores(usePollStore, useVotesStore, useOptionsStore),

		sheetName() {
			// Not allowed characters for the sheet name: : \ / ? * [ ]
			// Strip them out
			// Stonger regex i.error. for file names: /[&/\\#,+()$~%.'":*?<>{}]/g
			const regex = /[\\/?*[\]]/g
			return this.pollStore.configuration.title.replaceAll(regex, '').slice(0, 31)
		},
	},

	methods: {
		t,
		async exportFile(exportType) {
			const participantsHeader = [t('polls', 'Participants')]
			const fromHeader = [t('polls', 'From')]
			const toHeader = [t('polls', 'To')]
			this.workBook = xlsxUtils.book_new()
			this.workBook.SheetNames.push(this.sheetName)
			this.sheetData = []

			if (['html', 'xlsx', 'ods'].includes(exportType)) {
				this.sheetData.push(
					[DOMPurify.sanitize(this.pollStore.configuration.title)],
					[DOMPurify.sanitize(this.pollStore.configuration.description)],
				)
			}

			if (this.pollStore.permissions.edit) {
				try {
					participantsHeader.push(t('polls', 'Email address'))
					fromHeader.push('')
					toHeader.push('')
					const response = await PollsAPI.getParticipantsEmailAddresses(this.$route.params.id)
					this.emailAddresses = response.data
				} catch (error) {
					if (error.name === 'CanceledError') return
				}
			}

			if (this.pollStore.type === 'textPoll') {
				if (['html'].includes(exportType)) {
					this.sheetData.push([
						...participantsHeader,
						...this.optionsStore.list.map((item) => DOMPurify.sanitize(item.text)),
					])
				} else {
					this.sheetData.push([
						...participantsHeader,
						...this.optionsStore.list.map((item) => item.text),
					])
				}

			} else if (['csv'].includes(exportType)) {
				this.sheetData.push([
					...participantsHeader,
					...this.optionsStore.list.map((option) => this.optionsStore.explodeDates(option).iso),
				])

			} else if (['html'].includes(exportType)) {
				this.sheetData.push([
					...participantsHeader,
					...this.optionsStore.list.map((option) => this.optionsStore.explodeDates(option).raw),
				])

			} else {
				this.sheetData.push([
					...fromHeader,
					...this.optionsStore.list.map((option) => this.optionsStore.explodeDates(option).from.dateTime),
				])
				this.sheetData.push([
					...toHeader,
					...this.optionsStore.list.map((option) => this.optionsStore.explodeDates(option).to.dateTime),
				])
			}

			if (['html', 'ods', 'xlsx'].includes(exportType)) {
				this.addVotesArray('symbols')
			} else if (['csv'].includes(exportType)) {
				this.addVotesArray('raw')
			} else {
				this.addVotesArray()
			}

			const workBookOutput = xlsxWrite(this.workBook, { bookType: exportType, type: 'binary' })
			saveAs(new Blob([this.s2ab(workBookOutput)], { type: 'application/octet-stream' }), `pollStore.${exportType}`)
		},

		addVotesArray(style) {
			this.pollStore.participants.forEach((participant) => {
				const votesLine = [participant.displayName]
				try {
					if (this.pollStore.permissions.edit) {
						votesLine.push(this.emailAddresses.find((item) => item.displayName === participant.displayName).emailAddress)
					}

					this.optionsStore.list.forEach((option, i) => {
						if (style === 'symbols') {
							votesLine.push(this.votesStore.getVote({ userId: participant.userId, option }).answerSymbol ?? '❌')
						} else if (style === 'raw') {
							votesLine.push(this.votesStore.getVote({ userId: participant.userId, option }).answer)
						} else {
							votesLine.push(this.votesStore.getVote({ userId: participant.userId, option }).answerTranslated ?? t('polls', 'No'))
						}
					})

					this.sheetData.push(votesLine)
				} catch (error) {
					// just skip this participant
				}
			})

			const workSheet = xlsxUtils.aoa_to_sheet(this.sheetData)
			this.workBook.Sheets[this.sheetName] = workSheet
		},

		s2ab(s) {
			const buf = new ArrayBuffer(s.length) // convert s to arrayBuffer
			const view = new Uint8Array(buf) // create uint8array as viewer
			for (let i = 0; i < s.length; i++) view[i] = s.charCodeAt(i) & 0xFF // convert to octet
			return buf
		},
	},
}
</script>
