<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Tests\Unit\Db;

use OCA\Circles\Api\v1\Circles as CirclesApi;
use OCA\Circles\Model\Circle as CirclesCircle;
use OCA\DAV\CardDAV\CardDavBackend;
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
	private string $contactUid = '';
	private int $contactBookId = 0;
	private string $circleId = '';

	protected function setUp(): void {
		parent::setUp();
		$this->userMapper = Server::get(UserMapper::class);

		// Create a test contact for Contact/ContactGroup tests
		if (Contact::isEnabled()) {
			$backend = Server::get(CardDavBackend::class);
			$books = $backend->getAddressBooksForUser('principals/users/admin');
			if (empty($books)) {
				$backend->createAddressBook('principals/users/admin', 'contacts', []);
				$books = $backend->getAddressBooksForUser('principals/users/admin');
			}
			$this->contactBookId = (int)$books[0]['id'];
			$this->contactUid = bin2hex(random_bytes(8));
			$vcard = "BEGIN:VCARD\r\nVERSION:3.0\r\nUID:{$this->contactUid}\r\nFN:Test Contact\r\nEMAIL:test_{$this->contactUid}@polls.example.com\r\nEND:VCARD";
			$backend->createCard($this->contactBookId, $this->contactUid . '.vcf', $vcard);
		}

		// Create a test circle for Circle tests
		if (Circle::isEnabled()) {
			\OC_User::setUserId('admin');
			$circle = CirclesApi::createCircle(CirclesCircle::CIRCLES_PERSONAL, 'TestPollsCircle_' . bin2hex(random_bytes(4)));
			$this->circleId = $circle->getUniqueId();
		}
	}

	protected function tearDown(): void {
		parent::tearDown();

		if ($this->contactBookId > 0 && $this->contactUid !== '') {
			$backend = Server::get(CardDavBackend::class);
			$backend->deleteCard($this->contactBookId, $this->contactUid . '.vcf');
		}

		if ($this->circleId !== '') {
			try {
				CirclesApi::destroyCircle($this->circleId);
			} catch (\Exception $e) {
				// ignore cleanup errors
			}
		}
	}

	// --- getUserObject ---
	// User/Admin use 'admin' (created by NC install in CI).
	// Group uses 'admin' group (also created by NC install).
	// Contact/ContactGroup use a vCard created in setUp() via CardDavBackend.
	// Circle uses a circle created in setUp() via the Circles v1 API.

	public function testGetUserObjectReturnsUser(): void {
		$user = $this->userMapper->getUserObject(User::TYPE, 'admin');
		$this->assertInstanceOf(User::class, $user);
	}

	public function testGetUserObjectReturnsAdmin(): void {
		$user = $this->userMapper->getUserObject(Admin::TYPE, 'admin');
		$this->assertInstanceOf(Admin::class, $user);
	}

	public function testGetUserObjectReturnsGroup(): void {
		$user = $this->userMapper->getUserObject(Group::TYPE, 'admin');
		$this->assertInstanceOf(Group::class, $user);
	}

	public function testGetUserObjectReturnsContact(): void {
		$this->assertNotEmpty($this->contactUid, 'Contacts app not enabled or contact creation failed');
		$user = $this->userMapper->getUserObject(Contact::TYPE, $this->contactUid);
		$this->assertInstanceOf(Contact::class, $user);
	}

	public function testGetUserObjectReturnsContactGroup(): void {
		// ContactGroup constructor only checks isEnabled() — no data lookup needed
		$user = $this->userMapper->getUserObject(ContactGroup::TYPE, 'TestGroup');
		$this->assertInstanceOf(ContactGroup::class, $user);
	}

	public function testGetUserObjectReturnsCircle(): void {
		$this->assertNotEmpty($this->circleId, 'Circles app not enabled or circle creation failed');
		$user = $this->userMapper->getUserObject(Circle::TYPE, $this->circleId);
		$this->assertInstanceOf(Circle::class, $user);
	}

	public function testGetUserObjectReturnsGhost(): void {
		$user = $this->userMapper->getUserObject(Ghost::TYPE, 'ghost1');
		$this->assertInstanceOf(Ghost::class, $user);
	}

	public function testGetUserObjectReturnsEmail(): void {
		$user = $this->userMapper->getUserObject(Email::TYPE, 'email_user', 'Display', 'test@example.com', 'en');
		$this->assertInstanceOf(Email::class, $user);
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
