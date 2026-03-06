<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Tests\Unit\Db;

use OCA\Polls\Db\UserMapper;
use OCA\Polls\Exceptions\InvalidShareTypeException;
use OCA\Polls\Model\Group\Circle;
use OCA\Polls\Model\Group\ContactGroup;
use OCA\Polls\Model\Group\Group;
use OCA\Polls\Model\User\Admin;
use OCA\Polls\Model\User\Contact;
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

	// --- getUserObject: pure factory, no DB needed ---

	public function testGetUserObjectReturnsUser(): void {
		$user = $this->userMapper->getUserObject(User::TYPE, 'user1');
		$this->assertInstanceOf(User::class, $user);
	}

	public function testGetUserObjectReturnsAdmin(): void {
		$user = $this->userMapper->getUserObject(Admin::TYPE, 'admin1');
		$this->assertInstanceOf(Admin::class, $user);
	}

	public function testGetUserObjectReturnsGhost(): void {
		$user = $this->userMapper->getUserObject(Ghost::TYPE, 'ghost1');
		$this->assertInstanceOf(Ghost::class, $user);
	}

	public function testGetUserObjectReturnsGroup(): void {
		$user = $this->userMapper->getUserObject(Group::TYPE, 'group1');
		$this->assertInstanceOf(Group::class, $user);
	}

	public function testGetUserObjectReturnsEmail(): void {
		$user = $this->userMapper->getUserObject(Email::TYPE, 'email_user', 'Display', 'test@example.com', 'en');
		$this->assertInstanceOf(Email::class, $user);
	}

	public function testGetUserObjectReturnsContact(): void {
		$user = $this->userMapper->getUserObject(Contact::TYPE, 'contact1');
		$this->assertInstanceOf(Contact::class, $user);
	}

	public function testGetUserObjectReturnsContactGroup(): void {
		$user = $this->userMapper->getUserObject(ContactGroup::TYPE, 'cg1');
		$this->assertInstanceOf(ContactGroup::class, $user);
	}

	public function testGetUserObjectReturnsCircle(): void {
		$user = $this->userMapper->getUserObject(Circle::TYPE, 'circle1');
		$this->assertInstanceOf(Circle::class, $user);
	}

	public function testGetUserObjectReturnsExternalGenericUser(): void {
		$user = $this->userMapper->getUserObject(UserBase::TYPE_EXTERNAL, 'ext1', 'External User');
		$this->assertInstanceOf(GenericUser::class, $user);
	}

	public function testGetUserObjectReturnsPublicGenericUser(): void {
		$user = $this->userMapper->getUserObject(UserBase::TYPE_PUBLIC, 'pub1', 'Public User');
		$this->assertInstanceOf(GenericUser::class, $user);
	}

	public function testGetUserObjectThrowsForInvalidType(): void {
		$this->expectException(InvalidShareTypeException::class);
		$this->userMapper->getUserObject('invalid_type', 'user1');
	}

	// --- getParticipant: DB-touching paths ---

	public function testGetParticipantWithEmptyUserIdReturnsEmptyType(): void {
		$user = $this->userMapper->getParticipant('', null);
		$this->assertSame(UserBase::TYPE_EMPTY, $user->getType());
	}

	public function testGetParticipantWithNonExistentUserReturnsGhost(): void {
		// User doesn't exist in NC, no share exists → falls back to Ghost
		$user = $this->userMapper->getParticipant('nonexistent_user_xyzzy_' . bin2hex(random_bytes(4)), null);
		$this->assertInstanceOf(Ghost::class, $user);
	}
}
