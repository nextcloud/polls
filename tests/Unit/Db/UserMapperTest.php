<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Tests\Unit\Db;

use OCA\Polls\Db\Share;
use OCA\Polls\Db\UserMapper;
use OCA\Polls\Exceptions\InvalidShareTypeException;
use OCA\Polls\Model\Group\Group;
use OCA\Polls\Model\User\Admin;
use OCA\Polls\Model\User\Email;
use OCA\Polls\Model\User\GenericUser;
use OCA\Polls\Model\User\Ghost;
use OCA\Polls\Model\User\User;
use OCA\Polls\Model\UserBase;
use OCA\Polls\Tests\Unit\UnitTestCase;
use OCP\Server;

class UserMapperTest extends UnitTestCase {
	private UserMapper $userMapper;

	protected function setUp(): void {
		parent::setUp();
		$this->userMapper = Server::get(UserMapper::class);
	}

	// --- getUserObject ---
	// User/Admin use 'admin' (created by NC install in CI).
	// Group uses 'admin' group (also created by NC install).
	// Contact/ContactGroup/Team require external apps — tested separately once available.

	public function testGetUserObjectReturnsUser(): void {
		$user = UserMapper::createUserObject(User::TYPE, 'admin');
		$this->assertInstanceOf(User::class, $user);
	}

	public function testGetUserObjectReturnsAdmin(): void {
		$user = UserMapper::createUserObject(Admin::TYPE, 'admin');
		$this->assertInstanceOf(Admin::class, $user);
	}

	public function testGetUserObjectReturnsGroup(): void {
		$user = UserMapper::createUserObject(Group::TYPE, 'admin');
		$this->assertInstanceOf(Group::class, $user);
	}

	public function testGetUserObjectReturnsGhost(): void {
		$user = UserMapper::createUserObject(Ghost::TYPE, 'ghost1');
		$this->assertInstanceOf(Ghost::class, $user);
	}

	public function testGetUserObjectReturnsEmail(): void {
		$user = UserMapper::createUserObject(Email::TYPE, 'email_user', 'Display', 'test@example.com', 'en');
		$this->assertInstanceOf(Email::class, $user);
	}

	public function testGetUserObjectReturnsExternalGenericUser(): void {
		$user = UserMapper::createUserObject(UserBase::TYPE_EXTERNAL, 'ext1', 'External User');
		$this->assertInstanceOf(GenericUser::class, $user);
	}

	public function testGetUserObjectReturnsPublicGenericUser(): void {
		$user = UserMapper::createUserObject(UserBase::TYPE_PUBLIC, 'pub1', 'Public User');
		$this->assertInstanceOf(GenericUser::class, $user);
	}

	public function testGetUserObjectThrowsForInvalidType(): void {
		$this->expectException(InvalidShareTypeException::class);
		UserMapper::createUserObject('invalid_type', 'user1');
	}

	// --- getUser: DB-touching paths ---

	public function testGetParticipantWithEmptyUserIdReturnsEmptyType(): void {
		$user = $this->userMapper->getUser('', null);
		$this->assertSame(UserBase::TYPE_EMPTY, $user->getType());
	}

	public function testGetParticipantWithNonExistentUserReturnsGhost(): void {
		// User doesn't exist in NC, no share exists → falls back to Ghost
		$user = $this->userMapper->getUser('nonexistent_user_xyzzy_' . bin2hex(random_bytes(4)), null);
		$this->assertInstanceOf(Ghost::class, $user);
	}

	public function testGetUserReturnsShareUserWhenNotInUserBase(): void {
		// User doesn't exist in NC, but has an email share → resolveUser() path
		$pollId = 999999;
		$userId = 'test_share_' . bin2hex(random_bytes(4)) . '@example.com';

		$share = new Share();
		$share->setPollId($pollId);
		$share->setUserId($userId);
		$share->setType(Share::TYPE_EMAIL);
		$share->setDisplayName('Test Share User');
		$share->setEmailAddress($userId);
		$share->setToken(bin2hex(random_bytes(16)));

		$createdShare = $this->userMapper->insert($share);

		try {
			$user = $this->userMapper->getUser($userId, $pollId);
			$this->assertInstanceOf(Email::class, $user);
		} finally {
			$this->userMapper->delete($createdShare);
		}
	}
}
