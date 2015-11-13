<?php
/**
 * author: whipstercz
 */

namespace App\Services\BootstrapForm;

use Collective\Html\FormBuilder;
use Collective\Html\HtmlBuilder;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Lang;

class BootstrapForm
{
	/**
	 * Illuminate HtmlBuilder instance.
	 *
	 * @var \Collective\Html\HtmlBuilder
	 */
	protected $html;

	/**
	 * Illuminate FormBuilder instance.
	 *
	 * @var \Collective\Html\FormBuilder
	 */
	protected $form;

	/**
	 * Illuminate Repository instance.
	 *
	 * @var \Illuminate\Config\Repository
	 */
	protected $config;

	/**
	 * Bootstrap form type class.
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * Bootstrap form left column class.
	 *
	 * @var string
	 */
	protected $leftColumnClass;

	/**
	 * Bootstrap form left column offset class.
	 *
	 * @var string
	 */
	protected $leftColumnOffsetClass;

	/**
	 * Bootstrap form right column class.
	 *
	 * @var string
	 */
	protected $rightColumnClass;

	//Column class override for current form group
	protected $tempLeftColumns = null;
	protected $tempRightColumns = null;

	/**
	 * Has been errors() helper used?
	 * @var bool
	 */
	protected $errorBagShown = false;

	/**
	 *  Option for override config
	 * @var bool
	 */
	protected $showErrorsInFormGroup = false;

	/**
	 * @var \Illuminate\Database\Eloquent\Model
	 */
	protected $model = null;

	protected $fieldId = null;


	/**
	 * Construct the class.
	 *
	 * @param  \Collective\Html\HtmlBuilder $html
	 * @param  \Collective\Html\FormBuilder $form
	 * @param  \Illuminate\Contracts\Config\Repository $config
	 */
	public function __construct(HtmlBuilder $html, FormBuilder $form, Config $config)
	{
		$this->html = $html;
		$this->form = $form;
		$this->config = $config;
	}

	/**
	 * Open a form while passing a model and the routes for storing or updating
	 * the model. This will set the correct route along with the correct
	 * method.
	 *
	 * @param  array $options
	 * @return string
	 */
	public function open(array $options = [])
	{
		// Set the HTML5 role.
		$options['role'] = 'form';

		// Set the class for the form type.
		if (!array_key_exists('class', $options)) {
			$options['class'] = $this->getType();
		}

		if ($ajax = array_pull($options, 'ajax')) {
			$options['class'] .= " ajax";
		}

		if (array_key_exists('left_column_class', $options)) {
			$this->setLeftColumnClass($options['left_column_class']);
		}

		if (array_key_exists('left_column_offset_class', $options)) {
			$this->setLeftColumnOffsetClass($options['left_column_offset_class']);
		}

		if (array_key_exists('right_column_class', $options)) {
			$this->setRightColumnClass($options['right_column_class']);
		}

		array_forget($options, [
			'left_column_class',
			'left_column_offset_class',
			'right_column_class'
		]);

		if (array_key_exists('show_errors_in_form_group', $options)) {
			$this->setDisplayErrorsInFormGroup((bool)$options['show_errors_in_form_group']);
		}
		if (array_key_exists('model', $options)) {
			return $this->model($options);
		}

		return $this->form->open($options);
	}

	/**
	 * Reset and close the form.
	 *
	 * @return string
	 */
	public function close()
	{
		$this->type = null;

		$this->leftColumnClass = $this->rightColumnClass = null;

		return $this->form->close();
	}

	/**
	 * Open a form configured for model binding.
	 *
	 * @param  array $options
	 * @return string
	 */
	protected function model($options)
	{
		$model = $options['model'];

		// If the form is passed a model, we'll use the update route to update
		// the model using the PUT method.
		if (!is_null($options['model']) && $options['model']->exists) {
			if ($deleteRoute = array_pull($options, 'destroy')) {
				$route = Str::contains($deleteRoute, '@') ? 'action' : 'route';

				$options[$route] = [$deleteRoute, $options['model']->getRouteKey()];
				$options['method'] = 'DELETE';
			} else {
				$route = Str::contains($options['update'], '@') ? 'action' : 'route';

				$options[$route] = [$options['update'], $options['model']->getRouteKey()];
				$options['method'] = 'PUT';
			}

		} else {
			// Otherwise, we're storing a brand new model using the POST method.
			$route = Str::contains($options['store'], '@') ? 'action' : 'route';

			$options[$route] = $options['store'];
			$options['method'] = 'POST';
		}

		// Forget the routes provided to the input.
		array_forget($options, ['model', 'update', 'store']);
		$this->model = $model;

		return $this->form->model($model, $options);
	}

	/**
	 * Open a vertical (standard) Bootstrap form.
	 *
	 * @param  array $options
	 * @return string
	 */
	public function vertical(array $options = [])
	{
		$this->setType(FormType::VERTICAL);

		return $this->open($options);
	}

	/**
	 * Open an inline Bootstrap form.
	 *
	 * @param  array $options
	 * @return string
	 */
	public function inline(array $options = [])
	{
		$this->setType(FormType::INLINE);

		return $this->open($options);
	}

	/**
	 * Open a horizontal Bootstrap form.
	 *
	 * @param  array $options
	 * @return string
	 */
	public function horizontal(array $options = [])
	{
		$this->setType(FormType::HORIZONTAL);

		return $this->open($options);
	}

	/**
	 * Create a Bootstrap static field.
	 *
	 * @param  string $name
	 * @param  string $label
	 * @param  string $value
	 * @param  array $options
	 * @return string
	 */
	public function staticField($name, $label = null, $value = null, array $options = [])
	{
		$this->useLabelAsOptions($label, $options);

		$options = array_merge(['class' => 'form-control-static'], $options);

		$label = $this->getLabelTitle($label, $name);
		$inputElement = '<p' . $this->html->attributes($options) . '>' . e($value) . '</p>';

		$wrapperOptions = $this->isHorizontal() ? ['class' => $this->getRightColumnClass()] : [];
		$wrapperElement = '<div' . $this->html->attributes($wrapperOptions) . '>' . $inputElement . $this->getFieldError($name) . '</div>';

		return $this->getFormGroupWithLabel($name, $label, $wrapperElement);
	}

	/**
	 * Create a Bootstrap text field input.
	 *
	 * @param  string $name
	 * @param  string $label
	 * @param  string $value
	 * @param  array $options
	 * @return string
	 */
	public function text($name, $label = null, $value = null, array $options = [])
	{
		$this->useLabelAsOptions($label, $options);
		return $this->input('text', $name, $label, $value, $options);
	}

	/**
	 * Create a Bootstrap email field input.
	 *
	 * @param  string $name
	 * @param  string $label
	 * @param  string $value
	 * @param  array $options
	 * @return string
	 */
	public function email($name = 'email', $label = null, $value = null, array $options = [])
	{
		$this->useLabelAsOptions($label, $options);
		return $this->input('email', $name, $label, $value, $options);
	}

	/**
	 * Create a Bootstrap textarea field input.
	 *
	 * @param  string $name
	 * @param  string $label
	 * @param  string $value
	 * @param  array $options
	 * @return string
	 */
	public function textarea($name, $label = null, $value = null, array $options = [])
	{
		$this->useLabelAsOptions($label, $options);
		return $this->input('textarea', $name, $label, $value, $options);
	}

	/**
	 * Create a Bootstrap password field input.
	 *
	 * @param  string $name
	 * @param  string $label
	 * @param  array $options
	 * @return string
	 */
	public function password($name = 'password', $label = null, array $options = [])
	{
		$this->useLabelAsOptions($label, $options);
		return $this->input('password', $name, $label, null, $options);
	}

	/**
	 * Create a Bootstrap checkbox input.
	 *
	 * @param  string   $name
	 * @param  string   $label
	 * @param  string   $value
	 * @param  bool     $checked
	 * @param  array    $options
	 * @return string
	 */
	public function checkboxBool($name, $label = null, $checked = null, array $options = []) {
		$hiddenElement = $this->form->hidden($name,0);
		$element = $this->checkbox($name,$label,1,$checked,$options);
		return $hiddenElement.$element;
	}

	/**
	 * Create a Bootstrap checkbox input.
	 *
	 * @param  string $name
	 * @param  string $label
	 * @param  string $value
	 * @param  bool $checked
	 * @param  array $options
	 * @return string
	 */
	public function checkbox($name, $label = null, $value = 1, $checked = null, array $options = [])
	{
		$this->useLabelAsOptions($label, $options);

		//Opening Field - do not set for in label
		$this->fieldId = $this->getInputHtmlId($name,$options);
		if ($labelCols = (int)array_pull($options, 'label_cols')) {
			$this->setTemporarylabelColumns($labelCols);
		}

		$label = $this->getLabelTitle($label, $name, false);
		$inputElement = $this->checkboxElement($name, $label, $value, $checked, false, $options);
		$error = $this->getFieldError($name);

		$wrapperOptions = $this->isHorizontal() ? ['class' => implode(' ', [$this->getLeftColumnOffsetClass(), $this->getRightColumnClass()])] : [];
		@$wrapperOptions['class'] .= $this->getFieldErrorClass($name,' has-error');
		$wrapperElement = '<div' . $this->html->attributes($wrapperOptions) . '>' . $inputElement .$error. '</div>';

		$html = $this->getFormGroup(null, $wrapperElement);
		$this->closeField();
		return $html;
	}

	/**
	 * Create a single Bootstrap checkbox element.
	 *
	 * @param  string $name
	 * @param  string $label
	 * @param  string $value
	 * @param  bool $checked
	 * @param  bool $inline
	 * @param  array $options
	 * @return string
	 */
	public function checkboxElement($name, $label = null, $value = 1, $checked = null, $inline = false, array $options = [])
	{
		$this->useLabelAsOptions($label, $options);
		$this->fieldId = $this->getInputHtmlId($name,$options);

		$label = $this->getLabelTitle($label, $name, false);
		$labelOptions = $inline ? ['class' => 'checkbox-inline'] : [];
//		$labelOptions['for'] = $this->fieldId; //not neede

		$options['id'] = $this->fieldId;

		$inputElement = $this->form->checkbox($name, $value, $checked, $options);
		$labelElement = '<label ' . $this->html->attributes($labelOptions) . '>' . $inputElement . $label . '</label>';

		return $inline ? $labelElement : '<div class="checkbox">' . $labelElement . '</div>';
	}

	/**
	 * Create a collection of Bootstrap checkboxes.
	 *
	 * @param  string $name
	 * @param  string $label
	 * @param  array $choices
	 * @param  array $checkedValues
	 * @param  bool $inline
	 * @param  array $options
	 * @return string
	 */
	public function checkboxes($name, $label = null, $choices = [], $checkedValues = [], $inline = false, array $options = [])
	{
		$this->useLabelAsOptions($label, $options);

		//Opening Field
		$this->fieldId = $this->getInputHtmlId($name,$options);
		if ($labelCols = (int)array_pull($options, 'label_cols')) {
			$this->setTemporarylabelColumns($labelCols);
		}

		$elements = '';

		foreach ($choices as $value => $choiceLabel) {
			$checked = in_array($value, (array)$checkedValues);

			$elements .= $this->checkboxElement($name, $choiceLabel, $value, $checked, $inline, $options);
		}

		$wrapperOptions = $this->isHorizontal() ? ['class' => $this->getRightColumnClass()] : [];
		$wrapperElement = '<div' . $this->html->attributes($wrapperOptions) . '>' . $elements . $this->getFieldError($name) . '</div>';

		$html =  $this->getFormGroupWithLabel($name, $label, $wrapperElement);
		$this->closeField();
		return $html;
	}

	/**
	 * Create a Bootstrap radio input.
	 *
	 * @param  string $name
	 * @param  string $label
	 * @param  string $value
	 * @param  bool $checked
	 * @param  array $options
	 * @return string
	 */
	public function radio($name, $label = null, $value = null, $checked = null, array $options = [])
	{
		$this->useLabelAsOptions($label, $options);

		//Opening Field
		$this->fieldId = $this->getInputHtmlId($name,$options);
		if ($labelCols = (int)array_pull($options, 'label_cols')) {
			$this->setTemporarylabelColumns($labelCols);
		}

		$label = $this->getLabelTitle($label, $name, false);
		$inputElement = $this->radioElement($name, $label, $value, $checked, false, $options);
		$error = $this->getFieldError($name);

		$wrapperOptions = $this->isHorizontal() ? ['class' => implode(' ', [$this->getLeftColumnOffsetClass(), $this->getRightColumnClass()])] : [];
		@$wrapperOptions['class'] .= $this->getFieldErrorClass($name,' has-error');
		$wrapperElement = '<div' . $this->html->attributes($wrapperOptions) . '>' . $inputElement . $error .'</div>';

		$html = $this->getFormGroup(null, $wrapperElement);
		$this->closeField();
		return $html;
	}

	/**
	 * Create a single Bootstrap radio input.
	 *
	 * @param  string $name
	 * @param  string $label
	 * @param  string $value
	 * @param  bool $checked
	 * @param  bool $inline
	 * @param  array $options
	 * @return string
	 */
	public function radioElement($name, $label = null, $value = null, $checked = null, $inline = false, array $options = [])
	{
		$this->useLabelAsOptions($label, $options);
		$label = $this->getLabelTitle($label, $name, false);
		$value = $value ?: $label;

		$labelOptions = $inline ? ['class' => 'radio-inline'] : [];

		$inputElement = $this->form->radio($name, $value, $checked, $options);
		$labelElement = '<label ' . $this->html->attributes($labelOptions) . '>' . $inputElement . $label . '</label>';

		return $inline ? $labelElement : '<div class="radio">' . $labelElement . '</div>';
	}

	/**
	 * Create a collection of Bootstrap radio inputs.
	 *
	 * @param  string $name
	 * @param  string $label
	 * @param  array $choices
	 * @param  string $checkedValue
	 * @param  bool $inline
	 * @param  array $options
	 * @return string
	 */
	public function radios($name, $label = null, $choices = [], $checkedValue = null, $inline = false, array $options = [])
	{
		$this->useLabelAsOptions($label, $options);

		//Opening Field
		$this->fieldId = $this->getInputHtmlId($name,$options);
		if ($labelCols = (int)array_pull($options, 'label_cols')) {
			$this->setTemporarylabelColumns($labelCols);
		}

		$elements = '';
		foreach ($choices as $value => $choiceLabel) {
			$checked = $value === $checkedValue;

			$elements .= $this->radioElement($name, $choiceLabel, $value, $checked, $inline, $options);
		}

		$wrapperOptions = $this->isHorizontal() ? ['class' =>$this->getRightColumnClass()] : [];
		$wrapperElement = '<div' . $this->html->attributes($wrapperOptions) . '>' . $elements . $this->getFieldError($name) . '</div>';

		return $this->getFormGroupWithLabel($name, $label, $wrapperElement);
	}

	/**
	 * Create a Bootstrap label.
	 *
	 * @param  string $name
	 * @param  string $value
	 * @param  array $options
	 * @return string
	 */
	public function label($name, $value = null, array $options = [])
	{
		$options = $this->getLabelOptions($options);
		$value = $this->getLabelTitle($value, $name);
		if ($value === false ) {
			return '';
		}
		if ( $_for = array_pull($options,'for')) {
			$for = $_for;
		} elseif (isset($this->fieldId)) {
			$for = $this->fieldId;
		} else {
			$for = $this->getInputHtmlId($name,$options);
		}
		return $this->form->label($for, $value, $options);
	}

	/**
	 * Create a Boostrap submit button.
	 *
	 * @param  string $value
	 * @param  array $options
	 * @return string
	 */
	public function submit($value = null, array $options = [])
	{
		//Opening Field
		$this->fieldId = $this->getInputHtmlId($value,$options);
		if ($labelCols = (int)array_pull($options, 'label_cols')) {
			$this->setTemporarylabelColumns($labelCols);
		}

		$options = array_merge(['class' => 'btn btn-primary'], $options);

		$inputElement = $this->form->submit($value, $options);

		$wrapperOptions = $this->isHorizontal() ? ['class' => implode(' ', [$this->getLeftColumnOffsetClass(), $this->getRightColumnClass()])] : [];
		$wrapperElement = '<div' . $this->html->attributes($wrapperOptions) . '>' . $inputElement . '</div>';

		$html = $this->getFormGroup(null, $wrapperElement);
		$this->closeField();
		return $html;
	}

	/**
	 * Create a Boostrap file upload button.
	 *
	 * @param  string $name
	 * @param  string $label
	 * @param  array $options
	 * @return string
	 */
	public function file($name, $label = null, array $options = [])
	{
		$this->useLabelAsOptions($label, $options);

		//Opening Field
		$this->fieldId = $this->getInputHtmlId($name,$options);
		if ($labelCols = (int)array_pull($options, 'label_cols')) {
			$this->setTemporarylabelColumns($labelCols);
		}

		$label = $this->getLabelTitle($label, $name);

		$options['id'] = $this->fieldId;
		$options = array_merge(['class' => 'filestyle', 'data-buttonBefore' => 'true'], $options);

		$inputElement = $this->form->input('file', $name, null, $options);

		$wrapperOptions = $this->isHorizontal() ? ['class' => $this->getRightColumnClass()] : [];
		$wrapperElement = '<div' . $this->html->attributes($wrapperOptions) . '>' . $inputElement . $this->getFieldError($name) . '</div>';

		$html = $this->getFormGroupWithLabel($name, $label, $wrapperElement);
		$this->closeField();
		return $html;
	}

	/**
	 * Create the input group for an element with the correct classes for errors.
	 *
	 * @param  string $type
	 * @param  string $name
	 * @param  string $label
	 * @param  string $value
	 * @param  array $options
	 * @return string
	 */
	public function input($type, $name, $label = null, $value = null, array $options = [])
	{
		$label = $this->getLabelTitle($label, $name);

		//Opening Field
		$this->fieldId = $this->getInputHtmlId($name,$options);
		if ($labelCols = (int)array_pull($options, 'label_cols')) {
			$this->setTemporarylabelColumns($labelCols);
		}

		$options = $this->getFieldOptions($name, $options);

		//render input-group-addon
		$beforeElement = "";
		if ($prependAddon = array_pull($options, 'prependAddon')) {
			if ($prependAddon == 'fa-calendar') {
				$prependAddon = '<span class="fa fa-calendar"></span>';
			} else if (Str::contains($prependAddon, 'glyphicon-')) {
				$prependAddon = sprintf('<span class="glyphicon %s"></span>', $prependAddon);
			}
			$beforeElement = sprintf('<div class="input-group-addon">%s</div>', $prependAddon);
		}
		$afterElement = "";
		if ($appendAddon = array_pull($options, 'appendAddon')) {
			if ($appendAddon == 'fa-calendar') {
				$appendAddon = '<span class="fa fa-calendar"></span>';
			} else if (Str::contains($appendAddon, 'glyphicon-')) {
				$appendAddon = sprintf('<span class="glyphicon %s"></span>', $appendAddon);
			}
			$afterElement = sprintf('<div class="input-group-addon">%s</div>', $appendAddon);
		}

		$inputElement = $type === 'password' ? $this->form->password($name, $options) : $this->form->{$type}($name, $value, $options);

		if ($afterElement || $beforeElement) {
			$innerHtml = '<div class="input-group">'
				. $beforeElement
				. $inputElement
				. $afterElement
				. "</div>";
		} else {
			$innerHtml = $inputElement;
		}

		$wrapperOptions = $this->isHorizontal() ? ['class' => $this->getRightColumnClass()] : [];
		$wrapperElement = '<div' . $this->html->attributes($wrapperOptions) . '>'
			. $innerHtml
			. $this->getFieldError($name)
			. '</div>';

		$html = $this->getFormGroupWithLabel($name, $label, $wrapperElement);
		$this->closeField();
		return $html;
	}

	/**
	 * Create a hidden field.
	 *
	 * @param  string $name
	 * @param  string $value
	 * @param  array $options
	 * @return string
	 */
	public function hidden($name, $value = null, $options = [])
	{
		if (!isset($options['id'])) {
			$options['id'] = $this->getInputHtmlId($name,$options);
		}
		return $this->form->hidden($name, $value, $options);
	}

	/**
	 * Create a select box field.
	 *
	 * @param  string $name
	 * @param  string $label
	 * @param  array $list
	 * @param  string $selected
	 * @param  array $options
	 * @return string
	 */
	public function select($name, $label = null, $list = [], $selected = null, array $options = [])
	{
		$this->useLabelAsOptions($label, $options);

		//Opening Field
		$this->fieldId = $this->getInputHtmlId($name,$options);
		if ($labelCols = (int)array_pull($options, 'label_cols')) {
			$this->setTemporarylabelColumns($labelCols);
		}

		$label = $this->getLabelTitle($label, $name);

		if ( @$options['multiple'] && !$this->endsWith($name,'[]')) {
			$name .= '[]';
		}

		if ($selected instanceof Collection) {
			$selected = $selected->toArray();
		}

		$options = $this->getFieldOptions($name, $options);

		$inputElement = $this->form->select($name, $list, $selected, $options);

		$wrapperOptions = $this->isHorizontal() ? ['class' => $this->getRightColumnClass()] : [];
		$wrapperElement = '<div' . $this->html->attributes($wrapperOptions) . '>' . $inputElement . $this->getFieldError($name) . '</div>';

		$html = $this->getFormGroupWithLabel($name, $label, $wrapperElement,$options);
		$this->closeField();
		return $html;
	}



	public function getFormattedDateValue($name,$value,$format){
		if (null === $value) {
			$value = $this->form->getValueAttribute($name);
		}
		if ($value instanceof \DateTime) {
			$value = $value->format($format);
		}
		return $value;
	}

	/**
	 * Create a Text input with datePicker class and Formatted Date Value
	 * @param $name
	 * @param null|string|array $label - array means $options
	 * @param mixed $value
	 * @param array $options
	 * @return string
	 */
	public function month($name,$label=null,$value=null,$options = []){
		$this->useLabelAsOptions($label,$options);
		$options = array_merge($options, ['type'=>'date']);
		$options = array_merge($options, ['class'=>$this->config->get('bootstrapForm.month_picker_class')]);
		if ( null === $format = array_pull($options,'format')) {
			$format = $this->config->get('bootstrapForm.month_format');
		}
		$value = $this->getFormattedDateValue($name,$value,$format);
		//append date format to the form data
		$formatFields = $this->hidden($name.'_format',$format);
		return $formatFields . $this->text($name,$label,$value,$options);
	}

	/**
	 * Create a Text input with datePicker class and Formatted Date Value
	 * @param $name
	 * @param null|string|array $label - array means $options
	 * @param mixed $value
	 * @param array $options
	 * @return string
	 */
	public function date($name,$label=null,$value=null,$options = []){
		$this->useLabelAsOptions($label,$options);
		$options = array_merge($options, ['type'=>'date']);
		$options = array_merge($options, ['class'=>$this->config->get('bootstrapForm.date_picker_class')]);

		if ( null === $format = array_pull($options,'format')) {
			$format = $this->config->get('bootstrapForm.date_format');
		}

		$value = $this->getFormattedDateValue($name,$value,$format);
		//append date format to the form data
		$formatFields = $this->hidden($name.'_format',$format);
		return $formatFields . $this->text($name,$label,$value,$options);
	}



	/**
	 * Get the label title for a form field, first by using the provided one
	 * or titleizing the field name.
	 *
	 * @param  string $label
	 * @param  string $name
	 * @param bool $addPostfix
	 * @return string
	 */
	protected function getLabelTitle($label, $name, $addPostfix = true)
	{
		if (is_null($label) && Lang::has("forms.{$name}")) {
			return Lang::get("forms.{$name}");
		}
		if (!empty($label)) {
			return $label;
		}
		$label = $label ?: $name;
		if (Str::contains($label, '[]')) {
			$label = str_replace("[]", "", $label);
		}
		if (Str::contains($label, '_id')) {
			$label = str_replace("_id", "", $label);
		}
		if (Str::contains($label, '_')) {
			$label = str_replace("_", " ", $label);
		}
		if ($addPostfix && !Str::contains($label, $this->config->get('bootstrapForm.label_postfix'))) {
			$label .= $this->config->get('bootstrapForm.label_postfix');
		}
		return Str::title($label);
	}

	/**
	 * Get a form group comprised of a label, form element and errors.
	 *
	 * @param  string $name
	 * @param  string $value
	 * @param  string $element
	 * @return string
	 */
	protected function getFormGroupWithLabel($name, $value, $element, $options = [])
	{
		$options = $this->getFormGroupOptions($name);
		$html = '<div' . $this->html->attributes($options) . '>' . $this->label($name, $value ) . $element . '</div>';
		$this->setTemporarylabelColumns(null);
		return $html;
	}

	/**
	 * Get a form group.
	 *
	 * @param  string $name
	 * @param  string $element
	 * @return string
	 */
	public function getFormGroup($name = null, $element)
	{
		$options = $this->getFormGroupOptions($name);
		$html = '<div' . $this->html->attributes($options) . '>' . $element . '</div>';
		$this->setTemporarylabelColumns(null);
		return $html;
	}

	/**
	 * Merge the options provided for a form group with the default options
	 * required for Bootstrap styling.
	 *
	 * @param  string $name
	 * @param  array $options
	 * @return array
	 */
	protected function getFormGroupOptions($name = null, array $options = [])
	{
		$class = 'form-group';

		if ($name) {
			$class .= ' ' . $this->getFieldErrorClass($name);
		}

		return array_merge(['class' => $class], $options);
	}

	/**
	 * Merge the options provided for a field with the default options
	 * required for Bootstrap styling.
	 *
	 * @param  array $options
	 * @return array
	 */
	protected function getFieldOptions($name, array $options = [])
	{
		$options['class'] = trim('form-control ' . $this->getFieldOptionsClass($options));
		if (!isset($options['id'])) {
			$options['id'] = $this->fieldId ?: $this->getInputHtmlId($name,$options);
		}
		return $options;
	}

	/**
	 * Returns the class property from the options, or the empty string
	 *
	 * @param   array $options
	 * @return  string
	 */
	protected function getFieldOptionsClass(array $options = [])
	{
		return array_get($options, 'class');
	}

	/**
	 * Merge the options provided for a label with the default options
	 * required for Bootstrap styling.
	 *
	 * @param  array $options
	 * @return array
	 */
	protected function getLabelOptions(array $options = [])
	{
		$class = 'control-label';
		if ($this->isHorizontal()) {
			$class .= ' ' . $this->getLeftColumnClass();
		}

		return array_merge(['class' => trim($class)], $options);
	}

	/**
	 * Get the form type.
	 *
	 * @return string
	 */
	public function getType()
	{
		return isset($this->type) ? $this->type : $this->config->get('bootstrapForm.type');
	}

	/**
	 * Determine if the form is of a horizontal type.
	 *
	 * @return bool
	 */
	public function isHorizontal()
	{
		return $this->getType() === FormType::HORIZONTAL;
	}

	/**
	 * Set the form type.
	 *
	 * @param  string $type
	 * @return void
	 */
	public function setType($type)
	{
		$this->type = $type;
	}


	/**
	 * Get the column class for the left column offset of a horizontal form.
	 *
	 * @return string
	 */
	public function getLeftColumnClass()
	{
		$class = $this->leftColumnClass ?: $this->config->get('bootstrapForm.left_column_class');
		if ($this->tempLeftColumns) {
			$classes = explode(' ',$class);
			$newClasses = [];
			foreach ($classes as $class) {
				$newClasses[] = preg_replace('/\d/', "", $class) . $this->tempLeftColumns;
			}
			$class = implode(' ',$newClasses);
		}
		return $class;
	}


	/**
	 * Get the column class for the right column of a horizontal form.
	 *
	 * @return string
	 */
	public function getRightColumnClass()
	{
		$class = $this->rightColumnClass ?: $this->config->get('bootstrapForm.right_column_class');
		if ($this->tempRightColumns) {
			$classes = explode(' ',$class);
			$newClasses = [];
			foreach ($classes as $class) {
				$newClasses[] = preg_replace('/\d/', "", $class) . $this->tempRightColumns;
			}
			$class = implode(' ',$newClasses);
		}
		return $class;
	}


	/**
	 * Set the column class for the left column of a horizontal form.
	 *
	 * @param  string $class
	 * @return void
	 */
	public function setLeftColumnClass($class)
	{
		$this->leftColumnClass = $class;
	}

	/**
	 * Get the column class for the left column offset of a horizontal form.
	 *
	 * @return string
	 */
	public function getLeftColumnOffsetClass()
	{
		$class = $this->leftColumnOffsetClass ?: $this->config->get('bootstrapForm.left_column_offset_class');
		if ($this->tempLeftColumns) {
			$classes = explode(' ',$class);
			$newClasses = [];
			foreach ($classes as $class) {
				$newClasses[] = preg_replace('/\d/', "", $class) . $this->tempLeftColumns;
			}
			$class = implode(' ',$newClasses);
		}
		return $class;
	}

	/**
	 * Set the column class for the left column offset of a horizontal form.
	 *
	 * @param  string $class
	 * @return void
	 */
	public function setLeftColumnOffsetClass($class)
	{
		$this->leftColumnOffsetClass = $class;
	}


	/**
	 * Set the column class for the right column of a horizontal form.
	 *
	 * @param  string $lcass
	 * @return void
	 */
	public function setRightColumnClass($class)
	{
		$this->rightColumnClass = $class;
	}

	/**
	 * Get the MessageBag of errors that is populated by the
	 * validator.
	 *
	 * @return \Illuminate\Support\MessageBag
	 */
	protected function getErrors()
	{
		return $this->form->getSessionStore()->get('errors');
	}

	/**
	 * Get the first error for a given field, using the provided
	 * format, defaulting to the normal Bootstrap 3 format.
	 *
	 * @param  string $field
	 * @param  string $format
	 * @return mixed
	 */
	protected function getFieldError($field, $format = '<span class="help-block">:message</span>')
	{
		if (!$this->shouldDisplayErrorInFormGroup()) {
			return null;
		}
		if ($this->getErrors()) {
			$allErrors = $this->config->get('bootstrapForm.show_all_errors');

			if ($allErrors) {
				return implode('', $this->getErrors()->get($field, $format));
			}

			return $this->getErrors()->first($field, $format);
		}
		return null;
	}

	/**
	 * Return the error class if the given field has associated
	 * errors, defaulting to the normal Bootstrap 3 error class.
	 *
	 * @param  string $field
	 * @param  string $class
	 * @return string
	 */
	protected function getFieldErrorClass($field, $class = 'has-error')
	{
		//translate notation  name[0] => name.0
		if (strpos($field,"[") >= 0 ) {
			$chunks = explode('[',$field);
			array_walk($chunks,function(&$value,$key){
				$value = str_replace(']','',$value);
			});
			$field = implode('.',$chunks);
		}

		$hasErrors = $this->getErrors() && $this->getErrors()->first($field);
		return $hasErrors ? $class : null;
	}

	/**
	 * if label is_array use it as $options
	 * @param null|string|array $label
	 * @param array $options
	 */
	protected function useLabelAsOptions(&$label, &$options)
	{
		if (is_array($label) && count($options) == 0) {
			$options = $label;
			$label = null;
		}
	}

	public function errors($containerHtmlId = 'errors')
	{
		$this->errorBagShown = true;
		$errorHtml = '';
		if ($this->getErrors()) {
//			$errorBag = new ViewErrorBag($this->getErrors());
			$errorHtml = \View::make('inspinia::errors')->render();
		}
		return sprintf('<div id="%s">%s</div>', $containerHtmlId, $errorHtml);
	}

	protected function shouldDisplayErrorInFormGroup()
	{
		$errorsInFormGroup = $this->config->get('bootstrapForm.show_errors_in_form_group');
		return $this->showErrorsInFormGroup || $errorsInFormGroup || (!$this->errorBagShown && is_null($errorsInFormGroup));
	}

	public function setTemporarylabelColumns($cols)
	{
		if ($cols) {
			$this->tempLeftColumns = $cols;
			$this->tempRightColumns = 12 - $cols;
		} else {
			$this->tempLeftColumns = null;
			$this->tempRightColumns = null;
		}
	}


	public function setDisplayErrorsInFormGroup($enable)
	{
		$this->showErrorsInFormGroup = (bool)$enable;
		return $this;
	}

	protected function getInputHtmlId($name,$options)
	{
		if ( isset($options['id']) ) {
			return $options['id'];
		}
		return 'form-' . $name;
	}


	public function closeField(){
		$this->setTemporarylabelColumns(null);
		$this->fieldId = null;
	}

	protected function endsWith($haystack, $needle) {
		// search forward starting from end minus needle length characters
		return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
	}
}
