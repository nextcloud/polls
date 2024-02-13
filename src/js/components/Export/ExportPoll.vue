<!--
  - @copyright Copyright (c) 2018 René Gieling <github@dartcafe.de>
  -
  - @author René Gieling <github@dartcafe.de>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<NcActions>
		<template #icon>
			<ExportIcon />
		</template>
		<NcActionButton close-after-click
			:name="t('polls', 'Download Excel spreadsheet')"
			@click="exportFile('xlsx')">
			<template #icon>
				<ExcelIcon />
			</template>
		</NcActionButton>

		<NcActionButton close-after-click
			:name="t('polls', 'Download Open Document spreadsheet')"
			@click="exportFile('ods')">
			<template #icon>
				<FileTableIcon />
			</template>
		</NcActionButton>

		<NcActionButton close-after-click
			:name="t('polls', 'Download CSV file')"
			@click="exportFile('csv')">
			<template #icon>
				<CsvIcon />
			</template>
		</NcActionButton>

		<NcActionButton close-after-click
			:name="t('polls', 'Download HTML file')"
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
			poll: (state) => state.poll,
			options: (state) => state.options,
			votes: (state) => state.votes,
			permissions: (state) => state.poll.acl.permissions,
		}),

		sheetName() {
			// Not allowed characters for the sheet name: : \ / ? * [ ]
			// Strip them out
			// Stonger regex i.e. for file names: /[&/\\#,+()$~%.'":*?<>{}]/g
			const regex = /[\\/?*[\]]/g
			return this.poll.title.replaceAll(regex, '').slice(0, 31)
		},
	},

	methods: {
		async exportFile(type) {
			const participantsHeader = [t('polls', 'Participants')]
			const fromHeader = [t('polls', 'From')]
			const toHeader = [t('polls', 'To')]
			this.workBook = xlsxUtils.book_new()
			this.workBook.SheetNames.push(this.sheetName)
			this.sheetData = []

			if (['html', 'xlsx', 'ods'].includes(type)) {
				this.sheetData.push(
					[DOMPurify.sanitize(this.poll.title)],
					[DOMPurify.sanitize(this.poll.description)],
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

			if (this.poll.type === 'textPoll') {
				if (['html'].includes(type)) {
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

			} else if (['csv'].includes(type)) {
				this.sheetData.push([
					...participantsHeader,
					...this.options.list.map((option) => this.explodeDates(option).iso),
				])

			} else if (['html'].includes(type)) {
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

			if (['html', 'ods', 'xlsx'].includes(type)) {
				this.addVotesArray('symbols')
			} else if (['csv'].includes(type)) {
				this.addVotesArray('raw')
			} else {
				this.addVotesArray()
			}

			const workBookOutput = xlsxWrite(this.workBook, { bookType: type, type: 'binary' })
			saveAs(new Blob([this.s2ab(workBookOutput)], { type: 'application/octet-stream' }), `poll.${type}`)
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
							votesLine.push(this.getVote({ userId: participant.userId, option }).answerTranslated ?? t('polls', 'No'))
						}
					})

					this.sheetData.push(votesLine)
				} catch (e) {
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
