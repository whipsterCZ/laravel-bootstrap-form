<?php
/**
 *  @author: Daniel Kouba
 */

namespace App\Services\BootstrapForm\Facade;


use Illuminate\Support\Facades\Facade;

class BootstrapForm extends Facade
{
	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'bootstrapForm'; }
}