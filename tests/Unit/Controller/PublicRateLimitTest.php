<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Tests\Unit\Controller;

use OCA\Polls\AppInfo\Application;
use OCA\Polls\Controller\PublicController;
use OCA\Polls\Controller\ShareApiController;
use OCP\AppFramework\Http\Attribute\AnonRateLimit;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Public endpoints, which validate a user name against existing users, groups
 * and participants, reveal whether a name exists. They must be rate limited
 * to prevent mass enumeration of user names.
 *
 * The rate limit itself is enforced by the server's RateLimitingMiddleware,
 * so only the presence and configuration of the attributes is tested here.
 */
class PublicRateLimitTest extends TestCase {
	/**
	 * @return array<string, array{class-string, string}>
	 */
	public static function nameValidatingEndpoints(): array {
		return [
			'check username' => [PublicController::class, 'validatePublicDisplayName'],
			'change display name' => [PublicController::class, 'setDisplayName'],
			'register' => [PublicController::class, 'register'],
			'register (OCS)' => [ShareApiController::class, 'register'],
		];
	}

	/**
	 * @param class-string $controller
	 */
	#[DataProvider('nameValidatingEndpoints')]
	public function testEndpointHasAnonRateLimit(string $controller, string $method): void {
		$attributes = (new ReflectionMethod($controller, $method))->getAttributes(AnonRateLimit::class);

		$this->assertCount(1, $attributes, "$controller::$method must declare #[AnonRateLimit]");

		$rateLimit = $attributes[0]->newInstance();
		$this->assertSame(Application::PUBLIC_RATE_LIMIT, $rateLimit->getLimit());
		$this->assertSame(Application::PUBLIC_RATE_LIMIT_PERIOD, $rateLimit->getPeriod());
	}

	/**
	 * Without a UserRateLimit the middleware applies the anon limit to logged-in
	 * users as well. A (more generous) user limit would open a bypass by logging in.
	 *
	 * @param class-string $controller
	 */
	#[DataProvider('nameValidatingEndpoints')]
	public function testEndpointHasNoUserRateLimit(string $controller, string $method): void {
		$attributes = (new ReflectionMethod($controller, $method))->getAttributes(UserRateLimit::class);

		$this->assertCount(0, $attributes, "$controller::$method must not declare #[UserRateLimit]");
	}
}
