<?php

/*
 * EssentialsZ
 *
 * The essential plugin suite for PocketMine-MP servers
 *
 * @author Doma
 */

declare(strict_types=1);

namespace Doma\EssentialsZ\commands;

use Doma\EssentialsZ\I18n;
use function array_values;

/**
 * An exception whose message is a translated EssentialsZ message.
 */
class TranslatableException extends \Exception{

	/** @var list<string|int|float> */
	private array $tlArgs;

	public function __construct(
		private string $tlKey,
		string|int|float ...$args
	){
		$this->tlArgs = array_values($args);
		parent::__construct(I18n::tlLiteral($tlKey, ...$args));
	}

	public function getTlKey() : string{
		return $this->tlKey;
	}

	/**
	 * @return list<string|int|float>
	 */
	public function getTlArgs() : array{
		return $this->tlArgs;
	}
}
