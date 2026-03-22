<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Polls\Helper;

class NameGenerator {
	private const TRAITS = [
		'Accepting', 'Affectionate', 'Caring', 'Compassionate', 'Empathetic',
		'Happy', 'Loving', 'Sensitive', 'Warmhearted', 'Wholehearted',
		'Analytical', 'Articulate', 'Bright', 'Brilliant', 'Creative',
		'Curious', 'Educational', 'Imaginative', 'Ingenious', 'Inquisitive',
		'Intelligent', 'Knowledgeable', 'Logical', 'Observant', 'Reflective',
		'Scholarly', 'Smart', 'Thoughtful', 'Witty', 'Affable',
		'Amiable', 'Approachable', 'Charming', 'Communicative', 'Considerate',
		'Cooperative', 'Friendly', 'Gracious', 'Hospitable', 'Humorous',
		'Sociable', 'Supportive', 'Ethical', 'Fair', 'Faithful',
		'Genuine', 'Honest', 'Honorable', 'Just', 'Principled',
		'Respectful', 'Responsible', 'Truthful', 'Trustworthy', 'Active',
		'Adventurous', 'Ambitious', 'Daring', 'Dynamic', 'Eager',
		'Energetic', 'Enthusiastic', 'Fearless', 'Funny', 'Spirited',
		'Vibrant', 'Youthful', 'Zestful', 'Brave', 'Courageous',
		'Decisive', 'Determined', 'Hardworking', 'Persistent', 'Resolute',
		'Steadfast', 'Strong', 'Tenacious', 'Confident', 'Motivated',
		'Proactive', 'Strategic', 'Artful', 'Artistic', 'Eloquent',
		'Inventive', 'Playful', 'Cheerful', 'Easygoing', 'Grounded',
		'Humble', 'Idealistic', 'Inspiring', 'Kind', 'Modest',
		'Noble', 'Optimistic', 'Patient', 'Peaceful', 'Pleasant',
		'Selfless', 'Tranquil', 'Understanding', 'Dependable', 'Disciplined',
		'Loyal', 'Reliable', 'Capable', 'Competent', 'Efficient',
		'Helpful', 'Methodical', 'Organized', 'Productive', 'Skilled',
		'Adaptable', 'Flexible', 'Mindful', 'Tolerant', 'Versatile',
		'Positive', 'Uplifting', 'Agitated', 'Anxious', 'Dramatic',
		'Emotional', 'Excitable', 'Jittery', 'Melancholic', 'Moody',
		'Nervous', 'Restless', 'Tense', 'Worrier', 'Arrogant',
		'Boastful', 'Conceited', 'Haughty', 'Pretentious', 'Vain',
		'Devious', 'Shifty', 'Sneaky', 'Aloof', 'Aggressive',
		'Jealous', 'Stubborn', 'Grumpy', 'Cynical', 'Impulsive',
		'Reckless', 'Silly', 'Selfish', 'Bizarre', 'Odd',
	];

	private const ANIMALS = [
		'Albatross', 'Alligator', 'Alpaca', 'Armadillo', 'Axolotl',
		'Badger', 'Bear', 'Beaver', 'Bison', 'Buffalo',
		'Camel', 'Capybara', 'Chameleon', 'Cheetah', 'Chinchilla',
		'Chipmunk', 'Cobra', 'Condor', 'Crane', 'Crocodile',
		'Deer', 'Dingo', 'Dolphin', 'Duck', 'Eagle',
		'Echidna', 'Elk', 'Falcon', 'Ferret', 'Flamingo',
		'Fox', 'Gazelle', 'Gecko', 'Gibbon', 'Giraffe',
		'Gorilla', 'Hamster', 'Hawk', 'Hedgehog', 'Heron',
		'Hippo', 'Hyena', 'Iguana', 'Impala', 'Jaguar',
		'Kangaroo', 'Koala', 'Leopard', 'Lion', 'Llama',
		'Lynx', 'Meerkat', 'Mongoose', 'Moose', 'Orangutan',
		'Oryx', 'Otter', 'Owl', 'Panda', 'Pangolin',
		'Parrot', 'Peacock', 'Pelican', 'Penguin', 'Platypus',
		'Porcupine', 'Python', 'Quokka', 'Raccoon', 'Raven',
		'Rhino', 'Salamander', 'Seal', 'Skunk', 'Sloth',
		'Squirrel', 'Stork', 'Swan', 'Tapir', 'Tiger',
		'Tortoise', 'Toucan', 'Turtle', 'Wallaby', 'Walrus',
		'Weasel', 'Whale', 'Wildebeest', 'Wolf', 'Wombat',
		'Yak', 'Zebra',
	];

	/**
	 * Generate a reproducible display name from one or more input strings.
	 * Inputs are combined and hashed so that the source values
	 * cannot easily be reverse-calculated.
	 */
	public static function generate(string ...$parts): string {
		$hash = hash('sha256', implode('|', $parts));

		$traitIndex = abs((int)hexdec(substr($hash, 0, 8))) % count(self::TRAITS);
		$animalIndex = abs((int)hexdec(substr($hash, 8, 8))) % count(self::ANIMALS);

		return self::TRAITS[$traitIndex] . ' ' . self::ANIMALS[$animalIndex];
	}

	public static function generateRandom(): string {
		return self::generate(bin2hex(random_bytes(16)), bin2hex(random_bytes(12)));
	}
}
