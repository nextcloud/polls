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
import { mapGetters, mapState } from 'vuex'
import { saveAs } from 'file-saver'
import { utils as xlsxUtils, write as xlsxWrite } from 'xlsx'
import { showError } from '@nextcloud/dialogs'
import { NcActions, NcActionButton } from '@nextcloud/vue'
import ExcelIcon from 'vue-material-design-icons/MicrosoftExcel.vue'
import FileTableIcon from 'vue-material-design-icons/FileTableOutline.vue'
import CsvIcon from 'vue-material-design-icons/FileDelimited.vue'
import XmlIcon from 'vue-material-design-icons/Xml.vue'
import ExportIcon from 'vue-material-design-icons/FileDownloadOutline.vue'
import { PollsAPI } from '../../Api/index.js'
import DOMPurify from 'dompurify'

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
		...mapGetters({
			participants: 'poll/participantsVoted',
			getVote: 'votes/getVote',
			explodeDates: 'options/explodeDates',
		}),
		...mapState({
			pollType: (state) => state.poll.type,
			pollConfiguration: (state) => state.poll.configuration,
			options: (state) => state.options,
			permissions: (state) => state.poll.permissions,
		}),

		sheetName() {
			// Not allowed characters for the sheet name: : \ / ? * [ ]
			// Strip them out
			// Stonger regex i.error. for file names: /[&/\\#,+()$~%.'":*?<>{}]/g
			const regex = /[:\\/?*[\]]/g
			const title = this.pollConfiguration.title.replaceAll(regex, '').slice(0, 31)
			return title || 'polls_export'
		},
	},

	methods: {
		async exportFile(exportType) {
			const participantsHeader = [t('polls', 'Participants')]
			const fromHeader = [t('polls', 'From')]
			const toHeader = [t('polls', 'To')]
			this.workBook = xlsxUtils.book_new()
			this.workBook.SheetNames.push(this.sheetName)
			this.sheetData = []

			if (['html', 'xlsx', 'ods'].includes(exportType)) {
				this.sheetData.push(
					[DOMPurify.sanitize(this.pollConfiguration.title)],
					[DOMPurify.sanitize(this.pollConfiguration.description)],
				)
			}

			if (this.permissions.edit) {
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

			if (this.pollType === 'textPoll') {
				if (['html'].includes(exportType)) {
					this.sheetData.push([
						...participantsHeader,
						...this.options.list.map((item) => DOMPurify.sanitize(item.text)),
					])
				} else {
					this.sheetData.push([
						...participantsHeader,
						...this.options.list.map((item) => item.text),
					])
				}

			} else if (['csv'].includes(exportType)) {
				this.sheetData.push([
					...participantsHeader,
					...this.options.list.map((option) => this.explodeDates(option).iso),
				])

			} else if (['html'].includes(exportType)) {
				this.sheetData.push([
					...participantsHeader,
					...this.options.list.map((option) => this.explodeDates(option).raw),
				])

			} else {
				this.sheetData.push([
					...fromHeader,
					...this.options.list.map((option) => this.explodeDates(option).from.dateTime),
				])
				this.sheetData.push([
					...toHeader,
					...this.options.list.map((option) => this.explodeDates(option).to.dateTime),
				])
			}

			if (['html', 'ods', 'xlsx'].includes(exportType)) {
				this.addVotesArray('symbols')
			} else if (['csv'].includes(exportType)) {
				this.addVotesArray('raw')
			} else {
				this.addVotesArray()
			}

			try {
				const workBookOutput = xlsxWrite(this.workBook, { bookType: exportType, type: 'binary' })
				saveAs(new Blob([this.s2ab(workBookOutput)], { type: 'application/octet-stream' }), `poll.${exportType}`)
			} catch (error) {
				console.error(error)
				showError(t('polls', 'Error exporting file.'))
			}
		},

		getTranslatedAnswer(answer) {
			if (answer === 'yes') {
				return t('polls', 'Yes')
			}
			if (answer === 'maybe') {
				return t('polls', 'Maybe')
			}
			return t('polls', 'No')
		},
		addVotesArray(style) {
			this.participants.forEach((participant) => {
				const votesLine = [participant.displayName]
				try {
					if (this.permissions.edit) {
						votesLine.push(this.emailAddresses.find((item) => item.displayName === participant.displayName).emailAddress)
					}

					this.options.list.forEach((option, i) => {
						if (style === 'symbols') {
							votesLine.push(this.getVote({ userId: participant.userId, option }).answerSymbol ?? '❌')
						} else if (style === 'raw') {
							votesLine.push(this.getVote({ userId: participant.userId, option }).answer)
						} else {
							votesLine.push(this.getTranslatedAnswer(this.getVote({ userId: participant.userId, option }).answer))
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
