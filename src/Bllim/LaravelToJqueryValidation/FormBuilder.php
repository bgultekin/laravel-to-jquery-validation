<?php namespace Bllim\LaravelToJqueryValidation;
/**
 * This class is extending \Illuminate\Html\FormBuilder to make 
 * validation easy for both client and server side. Package convert 
 * laravel validation rules to jquery validator rules while using laravel 
 * FormBuilder.
 *
 * USAGE: Just pass $rules to Form::open($options, $rules) and use.
 * You can also pass by using Form::setValidation from controller or router
 * for coming first form::open.
 * When Form::close() is used, $rules are reset.
 *
 * NOTE: If you use min, max, size, between and type of input is different from string
 * don't forget to specify the type (by using numeric, integer).
 *
 * @package    Laravel Validation to Jquery Validation
 * @author     Bilal Gultekin <bilal@bilal.im>
 * @license    MIT
 * @see        Illuminate\Html\FormBuilder
 * @version    0.9
 */
use Illuminate\View\Compilers\BladeCompiler, Lang;

class FormBuilder extends \Illuminate\Html\FormBuilder {

	public $rules = [];

	public function __construct(\Illuminate\Html\HtmlBuilder $html, \Illuminate\Routing\UrlGenerator $url, $csrfToken)
	{
		parent::__construct($html, $url, $csrfToken);
	}

	/**
	 * Rules which specify input type is numeric
	 *
	 * @var array
	 */
	protected $numericRules = ['integer', 'numeric'];

	/**
	 * Set rules for validation
	 *
	 * @param array $rules 		Laravel validation rules
	 *
	 */
	public function setValidation($rules)
	{
		$this->setRules($rules);
	}

	/**
	 * Set rules for validation
	 *
	 * @param array $rules 		Laravel validation rules
	 *
	 */
	public function setRules($rules)
	{
		if($rules === null) return;
		$this->rules = $rules;
	}

	/**
	 * Get rules property
	 *
	 * @param array $rules 		Laravel validation rules
	 *
	 */
	public function getRules()
	{
		return $this->rules;
	}

	/**
	 * Reset validation rules
	 *
	 */
	public function resetValidation()
	{
		$this->rules = [];
	}

	/**
	 * Opens form, set rules
	 *
	 * @param array $rules 		Laravel validation rules
	 *
	 * @see Illuminate\Html\FormBuilder
	 */
	public function open(array $options = array(), $rules = null)
	{
		$this->setValidation($rules);
		
		return parent::open($options);
	}

	/**
	 * Create a new model based form builder.
	 *
	 * @param array $rules 		Laravel validation rules
	 *
	 * @see Illuminate\Html\FormBuilder
	 */
	public function model($model, array $options = array(), $rules = null)
	{
		$this->setValidation($rules);
		return parent::model($model, $options);
	}

	/**
	 * @see Illuminate\Html\FormBuilder
	 */
	public function input($type, $name, $value = null, $options = [])
	{
		$options = $this->laravelToJquery($name) + $options;
		return parent::input($type, $name, $value, $options);
	}

	/**
	 * @see Illuminate\Html\FormBuilder
	 */
	public function textarea($name, $value = null, $options = [])
	{
		$options = $this->laravelToJquery($name) + $options;
		return parent::textarea($name, $value, $options);
	}

	/**
	 * @see Illuminate\Html\FormBuilder
	 */
	public function select($name, $list = [], $selected = null, $options = [])
	{
		$options = $this->laravelToJquery($name) + $options;
		return parent::select($name, $list, $selected, $options);
	}

	protected function checkable($type, $name, $value, $checked, $options)
	{
		$options = $this->laravelToJquery($name) + $options;
		return parent::checkable($type, $name, $value, $checked, $options);
	}

	/**
	 * Closes form and reset $this->rules
	 * 
	 * @see Illuminate\Html\FormBuilder
	 */
	public function close()
	{
		$this->resetValidation();
		return parent::close();
	}

	/**
	 * Converts laravel rules to jquery validation rules with given $name
	 * 
	 * @see Illuminate\Html\FormBuilder
	 */
	public function laravelToJquery($inputName)
	{
		$jqueryRules = [];

		if(!$this->doesRuleExist($inputName))
		{
			return [];
		}

		$rules = explode('|', $this->getRule($inputName));
		$type = $this->getType($rules);

		foreach ($rules as $key => $value) 
		{
			$jqueryRules = $jqueryRules + $this->convertRule($value, $inputName, $type);
		}
		
		return $jqueryRules;
	}

	/**
	 * Converts laravel rules to jquery validation rules
	 * 
	 * @param array $rules 		Laravel validation rule
	 */
	protected function convertRule($rule, $attribute, $type)
	{
		$parsedRule = $this->parseValidationRule($rule);
		$jqueryAttrs = [];
		$ruleMethodName = $this->getRuleMethodName($parsedRule['name']);

		// if method not exists that means it is not implemented yet so return empty array
		if(!method_exists($this, $ruleMethodName))
		{
			return $jqueryAttrs;
		}

		$jqueryAttrs = $this->$ruleMethodName($parsedRule, $attribute, $type);
		
		// if validation messages of laravel is going to be used, convert them too
		if(\Config::get('laravel-to-jquery-validation::useLaravelMessages'))
		{
			$messageMethodName = $this->getMessageMethodName($parsedRule['name']);
			
			// if method not exists, get error message by using default way
			if(!method_exists($this, $messageMethodName))
			{
				$jqueryAttrs = $jqueryAttrs + $this->getErrorMessage($parsedRule['name'], $attribute);
			}
			else
			{
				$jqueryAttrs = $jqueryAttrs + $this->$messageMethodName($parsedRule, $attribute, $type);
			}
		}

		return $jqueryAttrs;
	}

	/**
	 * Sets error message
	 *
	 * @return string
	 */
	protected function getErrorMessage($laravelRule, $attribute)
	{
		// getting user friendly attribute name
		$attribute = $this->getAttributeName($attribute);
		$message = Lang::get('validation.'.$laravelRule, ['attribute' => $attribute]);

		return ['data-msg-'.$laravelRule => $message];
	}

	/**
	 * Checks if there is a rule for given input name
	 *
	 * @return string
	 */
	protected function doesRuleExist($name)
	{
		return isset($this->rules[$name]);
	}

	/**
	 * Get all rules and return type of input if rule specifies type
	 * Now, just for numeric
	 *
	 * @return string
	 */
	protected function getType($rules)
	{
		foreach ($rules as $key => $rule) {
			$parsedRule = $this->parseValidationRule($rule);
			if(in_array($parsedRule['name'], $this->numericRules))
			{
				return 'numeric';
			}
		}

		return 'string';
	}


	/**
	 * Get user friendly attribute name
	 *
	 * @return string
	 */
	protected function getAttributeName($attribute)
	{
		return !Lang::has('validation.attributes.'.$attribute) ? $attribute : Lang::get('validation.attributes.'.$attribute);
	}

	/**
	 * Gets laravel validation rule
	 *
	 * @return string
	 */
	protected function getRule($inputName)
	{
		return $this->rules[$inputName];
	}

	/**
	 * Gets convertion method name
	 *
	 * @return  string
	 */
	protected function getRuleMethodName($ruleName)
	{
		return '_convertRule'.studly_case($ruleName);
	}

	/**
	 * Gets message convertion method name
	 *
	 * @return  string
	 */
	protected function getMessageMethodName($ruleName)
	{
		return '_convertMessage'.studly_case($ruleName);
	}

	/**
	 * Parses validition rule of laravel
	 *
	 * @return array
	 */
	protected function parseValidationRule($rule)
	{
		$ruleArray = ['name' => '', 'parameters' => []];

		$explodedRule = explode(':', $rule);
		$ruleArray['name'] = array_shift($explodedRule);
		$ruleArray['parameters'] = explode(',', array_shift($explodedRule));

		return $ruleArray;
	}

	/**
	 * Rules convertions which returns attributes as an array
	 *
	 * @param  array ['name' => '', 'parameters' => []]
	 * @param  array 
	 * @param  array type of input
	 * @return  array
	 */
	
	protected function _convertRuleEmail($parsedRule, $attribute, $type) 
	{
		return ['data-rule-email' => 'true'];
	}

	protected function _convertRuleRequired($parsedRule, $attribute, $type) 
	{
		return ['data-rule-required' => 'true'];
	}

	protected function _convertRuleUrl($parsedRule, $attribute, $type) 
	{
		return ['data-rule-url' => 'true'];
	}

	protected function _convertRuleInteger($parsedRule, $attribute, $type) 
	{
		return ['data-rule-number' => 'true'];
	}

	protected function _convertRuleNumeric($parsedRule, $attribute, $type) 
	{
		return ['data-rule-number' => 'true'];
	}

	protected function _convertRuleIp($parsedRule, $attribute, $type) 
	{
		return ['data-rule-ipv4' => 'true'];
	}

	protected function _convertRuleSame($parsedRule, $attribute, $type) 
	{
		$value = vsprintf("*[name='%1s']", $parsedRule['parameters']);
		return ['data-rule-equalto' => $value];
	}

	protected function _convertRuleRegex($parsedRule, $attribute, $type) 
	{
		$rule = $parsedRule['parameters'][0];

		if(substr($rule, 0, 1) == substr($rule, -1, 1))
		{
			$rule = substr($rule, 1, -1);
		}

		return ['data-rule-regex' => $rule];
	}

	protected function _convertRuleAlpha($parsedRule, $attribute, $type) 
	{
		return ['data-rule-regex' => "^[A-Za-z _.-]+$"];
	}

	protected function _convertRuleAlphanum($parsedRule, $attribute, $type) 
	{
		return ['data-rule-regex' => "^[A-Za-z0-9 _.-]+$"];
	}

	protected function _convertRuleImage($parsedRule, $attribute, $type) 
	{
		return ['accept' => "image/*"];
	}

	protected function _convertRuleDate($parsedRule, $attribute, $type) 
	{
		return ['data-rule-date' => "true"];
	}

	protected function _convertRuleMin($parsedRule, $attribute, $type) 
	{
		switch ($type) 
		{
			case 'numeric':
				return ['data-rule-min' => vsprintf("%1s", $parsedRule['parameters'])];
				break;
			
			default:
				return ['data-rule-minlength' => vsprintf("%1s", $parsedRule['parameters'])];
				break;
		}
	}

	protected function _convertRuleMax($parsedRule, $attribute, $type) 
	{
		switch ($type) 
		{
			case 'numeric':
				return ['data-rule-max' => vsprintf("%1s", $parsedRule['parameters'])];
				break;
			
			default:
				return ['data-rule-maxlength' => vsprintf("%1s", $parsedRule['parameters'])];
				break;
		}
	}

	protected function _convertRuleBetween($parsedRule, $attribute, $type) 
	{
		switch ($type) 
		{
			case 'numeric':
				return ['data-rule-range' => vsprintf("%1s,%2s", $parsedRule['parameters'])];
				break;
			
			default:
				return ['data-rule-minlength' => $parsedRule['parameters'][0], 'data-rule-maxlength' =>  $parsedRule['parameters'][1]];
				break;
		}
	}
	
	/**
	 * Message convertions which returns attributes as an array
	 *
	 * @param  array ['name' => '', 'parameters' => []]
	 * @param  array 
	 * @param  array type of input
	 * @return  array
	 */
	
	protected function _convertMessageIp($parsedRule, $attribute, $type) 
	{
		$message = Lang::get('validation.'.$parsedRule['name'], ['attribute' => $attribute]);
		return ['data-msg-ipv4' => $message];
	}
	
	protected function _convertMessageAlpha($parsedRule, $attribute, $type) 
	{
		$message = Lang::get('validation.'.$parsedRule['name'], ['attribute' => $attribute]);
		return ['data-msg-regex' => $message];
	}
	
	protected function _convertMessageAlphanum($parsedRule, $attribute, $type) 
	{
		$message = Lang::get('validation.'.$parsedRule['name'], ['attribute' => $attribute]);
		return ['data-msg-regex' => $message];
	}
	
	protected function _convertMessageMax($parsedRule, $attribute, $type)
	{
		$message = Lang::get('validation.'.$parsedRule['name'].'.'.$type, ['attribute' => $attribute, 'max' => $parsedRule['parameters'][0]]);
		switch ($type) {
			case 'numeric':
				return ['data-msg-max' => $message];
				break;
			
			default:
				return ['data-msg-maxlength' => $message];
				break;
		}
	}
	
	protected function _convertMessageMin($parsedRule, $attribute, $type)
	{
		$message = Lang::get('validation.'.$parsedRule['name'].'.'.$type, ['attribute' => $attribute, 'min' => $parsedRule['parameters'][0]]);
		switch ($type) {
			case 'numeric':
				return ['data-msg-min' => $message];
				break;
			
			default:
				return ['data-msg-minlength' => $message];
				break;
		}
	}
	
	protected function _convertMessageBetween($parsedRule, $attribute, $type)
	{
		$message = Lang::get('validation.'.$parsedRule['name'].'.'.$type, ['attribute' => $attribute, 'min' => $parsedRule['parameters'][0], 'max' => $parsedRule['parameters'][1]]);
		switch ($type) {
			case 'numeric':
				return ['data-msg-range' => $message];
				break;
			
			default:
				return ['data-msg-minlength' => $message, 'data-msg-maxlength' => $message];
				break;
		}
	}


}