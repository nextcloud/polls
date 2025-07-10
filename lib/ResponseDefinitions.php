<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls;

/**
 * @psalm-type PollsPollPermissions = array {
 *  addOptions: boolean,
 *  shiftOptions: boolean,
 *  reorderOptions: boolean,
 *  archive: boolean,
 *  comment: boolean,
 *  confirmOptions: boolean,
 *  delete: boolean,
 *  edit: boolean,
 *  seeResults: boolean,
 *  seeUsernames: boolean,
 *  subscribe: boolean,
 *  view: boolean,
 *  vote: boolean,
 * }
 *
 * @psalm-type PollsCurrentUserStatus = array {
 * 	userRole: string,
 *  isLocked: boolean,
 *  isInvolved: boolean,
 *  isLoggedIn: boolean,
 *  isNoUser: boolean,
 *  isOwner: boolean,
 *  userId: string,
 *  orphanedVotes: int,
 *  yesVotes: int,
 *  countVotes: int,
 *  shareToken: string,
 *  groupInvitations: string[],
 * }
 *
 * @psalm-type PollsPollsStatus = array {
 * 	lastInteraction: int,
 *  created: int,
 *  deleted: boolean,
 *  expired: boolean,
 *  relevantThreshold: int,
 *  countParticipants: int,
 *  countProposals: int,
 * }
 *
 * @psalm-type PollsUser = array {
 *  array: string,
 *  categories?: string[],
 *  desc?: string,
 *  displayName: string,
 *  emailAddress?: string,
 * 	id: string,
 *  user?: string,
 *  isAdmin: boolean,
 *  isGuest: boolean,
 *  isNoUser: boolean,
 *  isUnrestrictedOwner: boolean,
 *  languageCode?: string,
 *  languageCodeIntl?: string,
 *  localeCode?: string,
 *  localeCodeIntl?: string,
 *  organisation?: string,
 *  subName?: string,
 *  subtitle?: string,
 *  timeZone?: string,
 *  type: string,
 *  userId: string,
 * }
 *
 * @psalm-type PollsPollConfiguration = array {
 * 	title: string,
 *  description: string,
 *  access: string,
 *  allowComment: boolean,
 *  allowMaybe: boolean,
 *  allowProposals: string,
 *  anonymous: boolean,
 *  autoReminder: boolean,
 *  expire: int,
 *  hideBookedUp: boolean,
 *  proposalsExpire: int,
 *  showResults: string,
 *  useNo: boolean,
 *  maxVotesPerOption: int,
 *  maxVotesPerUser: int,
 * }
 *
 * @psalm-type PollsPoll = array {
 *   id: int,
 *   type: string,
 *   descriptionSafe: string,
 *   configuration: PollsPollConfiguration,
 *   owner: PollsUser,
 *   status: PollsPollsStatus,
 *   currentUserStatus: PollsCurrentUserStatus,
 *   permissions: PollsPollPermissions,
 *   revealPartitians: boolean,
 * }
 * @psalm-suppress UnusedClass
 */
class ResponseDefinitions {
}
