<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls;

/**
 * @psalm-type PollsCapabilities = array{
 *   pollType: list<string>,
 *   voteVariant: list<string>,
 *   access: list<string>,
 *   showResults: list<string>,
 * }
 *
 * @psalm-type PollsSimpleOption = array{
 *   text?: string,
 *   timestamp?: int,
 *   isoDuration?: string,
 *   duration?: int,
 *   isoTimestamp?: string,
 * }
 *
 * @psalm-type PollsSequenceUnit = array{
 *   id: string,
 *   name: string,
 *   timeOption: bool,
 * }
 *
 * @psalm-type PollsSequence = array{
 *   repetitions: int,
 *   stepWidth: int,
 *   unit: PollsSequenceUnit,
 * }
 *
 * @psalm-type PollsPollPermissions = array{
 *   addOptions: bool,
 *   addShares: bool,
 *   addSharesExternal: bool,
 *   archive: bool,
 *   changeForeignVotes: bool,
 *   changeOwner: bool,
 *   clone: bool,
 *   comment: bool,
 *   confirmOptions: bool,
 *   deanonymize: bool,
 *   delete: bool,
 *   download: bool,
 *   edit: bool,
 *   reorderOptions: bool,
 *   seeResults: bool,
 *   seeUsernames: bool,
 *   subscribe: bool,
 *   takeOver: bool,
 *   view: bool,
 *   vote: bool,
 * }
 *
 * @psalm-type PollsCurrentUserStatus = array{
 *   userRole: string,
 *   isLocked: bool,
 *   isInvolved: bool,
 *   isLoggedIn: bool,
 *   isNoUser: bool,
 *   isOwner: bool,
 *   userId: string,
 *   orphanedVotes: int,
 *   yesVotes: int,
 *   noVotes: int,
 *   maybeVotes: int,
 *   countVotes: int,
 *   shareToken: string,
 *   groupInvitations: list<string>,
 *   pollGroupUserShares: list<string>,
 * }
 *
 * @psalm-type PollsPollsStatus = array{
 *   lastInteraction: int,
 *   created: int,
 *   isAnonymous: bool,
 *   isArchived: bool,
 *   isExpired: bool,
 *   isRealAnonymous: bool,
 *   relevantThreshold: int,
 *   deletionDate: int,
 *   archivedDate: int,
 *   countParticipants: int,
 *   maxVotes: int,
 *   maxOptionVotes: int,
 *   forcedViewMode: ?string,
 * }
 *
 * @psalm-type PollsUser = array{
 *   array: string,
 *   categories: list<string>,
 *   desc: string,
 *   displayName: string,
 *   emailAddress: string,
 *   id: string,
 *   user: ?string,
 *   isAdmin: bool,
 *   isGuest: bool,
 *   isNoUser: bool,
 *   isUnrestrictedOwner: bool,
 *   languageCode: string,
 *   languageCodeIntl?: string,
 *   localeCode: string,
 *   localeCodeIntl?: string,
 *   organisation: string,
 *   subname: string,
 *   subtitle: string,
 *   timeZone: string,
 *   type: string,
 *   userId: string,
 * }
 *
 * @psalm-type PollsPollConfiguration = array{
 *   title: string,
 *   description: string,
 *   access: string,
 *   allowComment: bool,
 *   allowDownload: bool,
 *   allowMaybe: bool,
 *   allowProposals: string,
 *   anonymous: bool,
 *   autoReminder: bool,
 *   collapseDescription: bool,
 *   expire: int,
 *   forceConfidentialComments: bool,
 *   forcedDisplayMode: string,
 *   hideBookedUp: bool,
 *   proposalsExpire: int,
 *   showResults: string,
 *   timezoneName: ?string,
 *   useNo: bool,
 *   maxVotesPerOption: int,
 *   maxVotesPerUser: int,
 * }
 *
 * @psalm-type PollsPoll = array{
 *   id: int,
 *   type: string,
 *   votingVariant: string,
 *   descriptionSafe: string,
 *   configuration: PollsPollConfiguration,
 *   owner: PollsUser,
 *   status: PollsPollsStatus,
 *   currentUserStatus: PollsCurrentUserStatus,
 *   permissions: PollsPollPermissions,
 *   pollGroups: list<int>,
 * }
 *
 * @psalm-type PollsComment = array{
 *   id: int,
 *   pollId: int,
 *   timestamp: int,
 *   comment: ?string,
 *   confidential: int,
 *   parent: int,
 *   deleted: int,
 *   user: PollsUser,
 *   recipient: ?PollsUser,
 * }
 *
 * @psalm-type PollsOptionVotes = array{
 *   no: int,
 *   yes: int,
 *   maybe: int,
 *   count: int,
 *   currentUser: ?string,
 * }
 *
 * @psalm-type PollsOption = array{
 *   id: int,
 *   pollId: int,
 *   text: string,
 *   timestamp: int,
 *   duration: int,
 *   isoTimestamp: ?string,
 *   isoDuration: ?string,
 *   deleted: int,
 *   order: int,
 *   confirmed: int,
 *   locked: bool,
 *   hash: string,
 *   isOwner: bool,
 *   votes: PollsOptionVotes,
 *   owner: ?PollsUser,
 * }
 *
 * @psalm-type PollsVote = array{
 *   id: int,
 *   pollId: int,
 *   optionText: string,
 *   answer: string,
 *   deleted: int,
 *   optionId: ?int,
 *   user: PollsUser,
 *   answerSymbol: string,
 * }
 *
 * @psalm-type PollsShare = array{
 *   id: int,
 *   token: string,
 *   type: string,
 *   pollId: ?int,
 *   groupId: ?int,
 *   invitationSent: bool,
 *   reminderSent: bool,
 *   locked: bool,
 *   label: string,
 *   URL: string,
 *   publicPollEmail: string,
 *   voted: bool,
 *   deleted: bool,
 *   user: PollsUser,
 * }
 *
 * @psalm-type PollsWatch = array{
 *   id: int,
 *   pollId: int,
 *   table: string,
 *   updated: int,
 * }
 *
 * @psalm-type PollsSentMailInfo = array{
 *   emailAddress: string,
 *   displayName: string,
 * }
 *
 * @psalm-type PollsAbortedMailInfo = array{
 *   emailAddress: string,
 *   displayName: string,
 *   reason: string,
 * }
 *
 * @psalm-type PollsSentResult = array{
 *   sentMails: list<PollsSentMailInfo>,
 *   abortedMails: list<PollsAbortedMailInfo>,
 *   countSentMails: int,
 *   countAbortedMails: int,
 * }
 *
 * @psalm-type PollsAppPermissions = array{
 *   allAccess: bool,
 *   addShares: bool,
 *   addSharesExternal: bool,
 *   changeForeignVotes: bool,
 *   comboView: bool,
 *   deanonymizePoll: bool,
 *   pollCreation: bool,
 *   pollDownload: bool,
 *   publicShares: bool,
 *   seeMailAddresses: bool,
 *   unrestrictedOwner: bool,
 * }
 *
 * @psalm-type PollsAppSettings = array{
 *   finalPrivacyUrl: string,
 *   finalImprintUrl: string,
 *   useLogin: bool,
 *   useActivity: bool,
 *   navigationPollsInList: bool,
 *   updateType: string,
 *   currentVersion: string,
 * }
 *
 * @psalm-type PollsSession = array{
 *   token: ?string,
 *   currentUser: PollsUser,
 *   appPermissions: PollsAppPermissions,
 *   appSettings: PollsAppSettings,
 * }
 *
 * @psalm-suppress UnusedClass
 */
class ResponseDefinitions {
}
