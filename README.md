# Laravel Bootstrap CSS Form Builder & helpers

  - Provides useful tools for working with Bootstrap CSS Forms and Laravel
  - inspired by Bootstrap-Form package
  - Form Builder accessible through `BootstrapForm` facade.
  
Installation
------------
1) Install Laravel Collective package via composer (HTML & Form Builder)
~~~~~ php
  composer require laravelcollective/html
~~~~~

2) Copy source code to your existing App - directories should **match service Namespace**
~~~~~ php
App/Services/BootstrapForm/
~~~~~

3) register service provider and Facade (Optional) in your **config/app.php**
~~~~~ php
'providers' => [
	...,
	Collective\Html\HtmlServiceProvider:class,
	App\Services\BootstrapForm\ServiceProvider::class,
],
'aliases' => [
	...,
	'Form'=> Collective\Html\FormFacade::class,
    'HTML'=> Collective\Html\HtmlFacade::class,
	'BootstrapForm' => App\Services\BootstrapForm\Facade\BootstrapForm::class,
	'BF' => App\Services\BootstrapForm\Facade\BootstrapForm::class, //optional shortcut
]
~~~~~


4) Copy package views and configuration by running command
~~~~~ php
php artisan vendor:publish
~~~~~


Usage
---------------------

## In blade views

This extension is inspired by `https://github.com/dwightwatson/bootstrap-form`.
  So you can use any methods from Bootstrap Form Builder

~~~~~ php
  BootForm::text($name,$label,$value,$attr)
~~~~~

Or you can use new helpers.
  - all input helpers renders whole form-group with label and input
  - all input has smart Labels - try to set `$label=null`
  - **checkboxBool** input uses hidden input for send unchecked state - it sends 0/1
  - **date** and *month* helper -  has `format` attribute, or check AppForm class for set default value
     - also adds hidden input with used date format available form `$_POST["{$name}_format"]`
     - also adds class 'date-picker' or 'month-picker' to the input
  - **errors** helper render whole errorBag and disable error message in form-group (by default)

~~~~~ php
BootstrapForm::open([
    'model' => $client,
    'update' => 'client.update',    //route name
    'store' => 'client.store',      //route name
    //AJAXify form - @see https://github.com/whipsterCZ/laravel-ajax
    'ajax' => false,
    //optional @see configuration/bootstrapForm.php
        'left_column_class' => 'col-md-2',
        'right_column_class' => 'col-md-10',
        'left_column_offset_class'=> 'col-md-offset-2',
    //optional -  checks if errorBag is shown
    'show_errors_in_form_group' => false,
    'files' => true //default=false
]);

BootstrapForm::errors()

$label = null; //Smart label - create label from name
$value = null;
$genders = [1=>'Male',2=>'Female'];

BootstrapForm::date('date_created',$label,$value,['format'=>'m/d/Y']);
BootstrapForm::month('date_created',$label,$value,['format'=>'m/d/Y']);
BootstrapForm::text('name');
BootstrapForm::checkbox('light_is',$label, $value, $checked ); //$value can be "on"  
BootstrapForm::checkboxBool('approval',$label, $checked); //$value is not needed
BootstrapForm::textarea('description');
BootstrapForm::select('sex',$label,$genders,$value,['placeholder'=>'Select gender','class'=>'select2']);
BootstrapForm::hidden('id',$value);
BootstrapForm::submit('Save');

BootstrapForm::close();

//@see BootstrapForm.php class for all helpers & configuration options

~~~~~

Many more features will be documented (&added) soon  :)