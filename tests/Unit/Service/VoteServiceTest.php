<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Tests\Unit\Service;

use OCA\Polls\Db\Option;
use OCA\Polls\Db\OptionMapper;
use OCA\Polls\Db\Poll;
use OCA\Polls\Db\PollMapper;
use OCA\Polls\Db\Share;
use OCA\Polls\Db\ShareMapper;
use OCA\Polls\Db\Vote;
use OCA\Polls\Db\VoteMapper;
use OCA\Polls\Service\VoteService;
use OCA\Polls\Tests\Unit\UnitTestCase;
use OCA\Polls\UserSession;
use OCP\ISession;
use OCP\Server;

class VoteServiceTest extends UnitTestCase {
	private VoteService $voteService;
	private PollMapper $pollMapper;
	private OptionMapper $optionMapper;
	private ShareMapper $shareMapper;
	private VoteMapper $voteMapper;
	private ISession $session;
	private UserSession $userSession;

	private Poll $poll;
	private Option $option;
	private Share $ownerShare;
	private Share $externalShare;

	private const EXTERNAL_USER_ID = 'ext_voter_test';

	protected function setUp(): void {
		parent::setUp();
		$this->session = Server::get(ISession::class);
		$this->pollMapper = Server::get(PollMapper::class);
		$this->optionMapper = Server::get(OptionMapper::class);
		$this->shareMapper = Server::get(ShareMapper::class);
		$this->voteMapper = Server::get(VoteMapper::class);
		$this->voteService = Server::get(VoteService::class);
		$this->userSession = Server::get(UserSession::class);

		// Poll owned by admin, private access, voting open (useNo=0 is default)
		$poll = $this->fm->instance('OCA\Polls\Db\Poll');
		$poll->setOwner('admin');
		$poll->setAccess(Poll::ACCESS_PRIVATE);
		$poll->setExpire(0);
		$this->poll = $this->pollMapper->insert($poll);

		// Option belonging to the poll
		$option = $this->fm->instance('OCA\Polls\Db\Option');
		$option->setPollId($this->poll->getId());
		$option->setOwner('admin');
		$this->option = $this->optionMapper->insert($option);

		// TYPE_USER share for admin — gives getCurrentUser() a User object (TYPE_USER)
		$ownerShare = $this->fm->instance('OCA\Polls\Db\Share');
		$ownerShare->setPollId($this->poll->getId());
		$ownerShare->setType(Share::TYPE_USER);
		$ownerShare->setUserId('admin');
		$this->ownerShare = $this->shareMapper->insert($ownerShare);

		// TYPE_EXTERNAL share for a simulated external participant
		$externalShare = $this->fm->instance('OCA\Polls\Db\Share');
		$externalShare->setPollId($this->poll->getId());
		$externalShare->setType(Share::TYPE_EXTERNAL);
		$externalShare->setUserId(self::EXTERNAL_USER_ID);
		$this->externalShare = $this->shareMapper->insert($externalShare);

		$this->loginAsOwner();
	}

	protected function tearDown(): void {
		parent::tearDown();
		$this->voteMapper->deleteByPollAndUserId($this->poll->getId(), 'admin');
		$this->voteMapper->deleteByPollAndUserId($this->poll->getId(), self::EXTERNAL_USER_ID);
		$this->shareMapper->delete($this->ownerShare);
		$this->shareMapper->delete($this->externalShare);
		$this->pollMapper->delete($this->poll);
	}

	// --- session helpers ---

	/**
	 * Login as poll owner via TYPE_USER share.
	 * UserSession resolves getCurrentUser() → User('admin') with TYPE_USER.
	 */
	private function loginAsOwner(): void {
		$this->userSession->cleanSession();
		$this->session->set(UserSession::SESSION_KEY_SHARE_TOKEN, $this->ownerShare->getToken());
		$this->session->set(UserSession::SESSION_KEY_USER_ID, 'admin');
	}

	/**
	 * Login as external participant via TYPE_EXTERNAL share.
	 * UserSession resolves getCurrentUser() → GenericUser with TYPE_EXTERNAL.
	 */
	private function loginAsExternalUser(): void {
		$this->userSession->cleanSession();
		$this->session->set(UserSession::SESSION_KEY_SHARE_TOKEN, $this->externalShare->getToken());
		$this->session->set(UserSession::SESSION_KEY_USER_ID, self::EXTERNAL_USER_ID);
	}

	// --- set vote ---

	public function testOwnerCanSetVoteYes(): void {
		$vote = $this->voteService->set($this->option->getId(), Vote::VOTE_YES);
		$this->assertInstanceOf(Vote::class, $vote);
		$this->assertSame(Vote::VOTE_YES, $vote->getVoteAnswer());
		$this->assertSame('admin', $vote->getUserId());
		$this->assertSame($this->poll->getId(), $vote->getPollId());
	}

	public function testOwnerCanSetVoteMaybe(): void {
		$vote = $this->voteService->set($this->option->getId(), Vote::VOTE_EVENTUALLY);
		$this->assertSame(Vote::VOTE_EVENTUALLY, $vote->getVoteAnswer());
	}

	public function testOwnerCanUpdateVoteFromYesToMaybe(): void {
		$this->voteService->set($this->option->getId(), Vote::VOTE_YES);
		$updated = $this->voteService->set($this->option->getId(), Vote::VOTE_EVENTUALLY);
		$this->assertSame(Vote::VOTE_EVENTUALLY, $updated->getVoteAnswer());
	}

	public function testSetSameAnswerIsIdempotent(): void {
		$first = $this->voteService->set($this->option->getId(), Vote::VOTE_YES);
		$second = $this->voteService->set($this->option->getId(), Vote::VOTE_YES);
		// Returns existing vote without change
		$this->assertSame($first->getId(), $second->getId());
		$this->assertSame(Vote::VOTE_YES, $second->getVoteAnswer());
	}

	public function testSetVoteNoDeletesExistingVoteWhenUseNoIsFalse(): void {
		// useNo=0 (default): setting 'no' on an existing vote deletes it
		$this->voteService->set($this->option->getId(), Vote::VOTE_YES);
		$result = $this->voteService->set($this->option->getId(), Vote::VOTE_NO);
		// Service returns the vote entity with empty answer after deletion
		$this->assertSame('', $result->getVoteAnswer());
	}

	public function testSetVoteNoStoredWhenUseNoIsTrue(): void {
		// Enable 'no' votes on the poll
		$this->poll->setUseNo(1);
		$this->pollMapper->update($this->poll);

		$vote = $this->voteService->set($this->option->getId(), Vote::VOTE_NO);
		$this->assertSame(Vote::VOTE_NO, $vote->getVoteAnswer());

		// Restore default
		$this->poll->setUseNo(0);
		$this->pollMapper->update($this->poll);
	}

	// --- external user ---

	public function testExternalUserCanVote(): void {
		$this->loginAsExternalUser();
		$vote = $this->voteService->set($this->option->getId(), Vote::VOTE_YES);
		$this->assertInstanceOf(Vote::class, $vote);
		$this->assertSame(Vote::VOTE_YES, $vote->getVoteAnswer());
		$this->assertSame(self::EXTERNAL_USER_ID, $vote->getUserId());
	}

	// --- list ---

	public function testOwnerCanListAllVotes(): void {
		// Create votes for both owner and external user
		$this->voteService->set($this->option->getId(), Vote::VOTE_YES);
		$this->loginAsExternalUser();
		$this->voteService->set($this->option->getId(), Vote::VOTE_YES);

		// Switch back to owner and list
		$this->loginAsOwner();
		$votes = $this->voteService->list($this->poll->getId());
		$this->assertCount(2, $votes);
	}

	// --- deleteUserFromPoll ---

	public function testOwnerCanDeleteOwnVotesFromPoll(): void {
		$this->voteService->set($this->option->getId(), Vote::VOTE_YES);
		$userId = $this->voteService->deleteUserFromPoll($this->poll->getId());
		$this->assertSame('admin', $userId);

		$remainingVotes = $this->voteMapper->findByPollAndUser($this->poll->getId(), 'admin');
		$this->assertEmpty($remainingVotes);
	}

	public function testOwnerCanDeleteExternalUserVotesFromPoll(): void {
		$this->loginAsExternalUser();
		$this->voteService->set($this->option->getId(), Vote::VOTE_YES);

		// Owner deletes the external user's votes
		$this->loginAsOwner();
		$userId = $this->voteService->deleteUserFromPoll($this->poll->getId(), self::EXTERNAL_USER_ID);
		$this->assertSame(self::EXTERNAL_USER_ID, $userId);

		$remainingVotes = $this->voteMapper->findByPollAndUser($this->poll->getId(), self::EXTERNAL_USER_ID);
		$this->assertEmpty($remainingVotes);
	}
}
