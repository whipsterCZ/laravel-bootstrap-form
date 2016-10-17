<?php

use App\Services\BootstrapForm\FormType;

return [

	/*
	|--------------------------------------------------------------------------
	| Form type
	|--------------------------------------------------------------------------
	|
	| Here you may specify the default form type for the open method. You have
	| the options of FormType::HORIZONTAL, FormType::VERTICAL and FormType::INLINE.
	|
	*/

	'type' => FormType::HORIZONTAL,

	/*
	|--------------------------------------------------------------------------
	| Bootstrap version
	|--------------------------------------------------------------------------
	|
	| Here you can specify Bootstrap CSS Version
	| the options of FormType::VERSION_3 (default) and FormType::VERSION_4
	|
	*/
	'version' => FormType::VERSION_3,

	/*
	|--------------------------------------------------------------------------
	| Horizontal form default sizing
	|--------------------------------------------------------------------------
	|
	| Here you may specify the default widths of the columns if you're using
	| the horizontal form type. You can use the Bootstrap grid classes as you
	| wish.
	|
	*/

	'left_column_class'  => 'col-sm-2 col-md-2',
	'right_column_class' => 'col-sm-10 col-md-10',

	'left_column_offset_class' => 'col-sm-offset-2 col-md-offset-2',

	/*
	|--------------------------------------------------------------------------
	| Error output
	|--------------------------------------------------------------------------
	|
	| Here you may specify the whether all the errors of an input should be
	| displayed or just the first one.
	|
	| if show_errors_in_form_group == null
	| Display errors will be show either in ErrorBag Container or Input Form Group
	|
	*/

	'show_all_errors' => false,
	'show_errors_in_form_group' => null, //null == AUTO


	/*
	|--------------------------------------------------------------------------
	| Automatic label
	|--------------------------------------------------------------------------
	|
	| if label=null
	| It converts snake_case to Snake Case and appends postfix
	| _id postfix is automatically removed
	|
	*/
	'label_postfix' => ":",


    /*
    |--------------------------------------------------------------------------
    | Date support in InspiniaForm
    |--------------------------------------------------------------------------
    |
    | if need change date formats, don't forget to update config of javascript datePicker Component
    |
    |--------------------------------------------------------------------------
     */
    'date_format' => 'd/m/Y',
    'month_format' => 'Y-m',
    'date_picker_class' => 'date-picker',
    'month_picker_class' => 'month-picker',

];
