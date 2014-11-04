## Laravel to Jquery Validation

**About**

This package makes validation rules defined in laravel work client-side by converting to jquery validation rules. It uses [Jquery Validation Plugin](http://jqueryvalidation.org/). It also allows to use laravel validation messages so you can show same messages for both sides.

### Feature Overview
- Converts validation rules from laravel to jquery validator
- Works with laravel form builder
- Can be set validation rule from controller
- Can distinguish between numeric input and string input
- Supports user friendly input names


### Installation

Require `bllim/laravel-to-jquery-validation` in composer.json and run `composer update`.

    {
        "require": {
            "laravel/framework": "4.0.*",
            ...
            "bllim/laravel-to-jquery-validation": "*"
        }
        ...
    }

Composer will download the package. After the package is downloaded, open `app/config/app.php` and add the service provider and alias as below:
```php
    'providers' => array(
        ...
        'Bllim\LaravelToJqueryValidation\LaravelToJqueryValidationServiceProvider',
    ),
```

Also you need to publish configuration file and assets by running the following Artisan commands.
```php
$ php artisan config:publish bllim/laravel-to-jquery-validation
$ php artisan asset:publish bllim/laravel-to-jquery-validation
```

### Usage
Since the package uses [Jquery Validation Plugin](http://jqueryvalidation.org/) you should include it in (and include [jquery](http://jquery.com/) of course) your views. Also for unsupported rules in jquery validator, you should include jquery.validate.laravel.js in your views, too. After assets published, they will be copied to your public folder. The last thing you should do at client side is initializing jquery validator plugin as below:
```html
<script type="text/javascript">
$('form').validate();
</script>
```

The package uses laravel Form Builder to make validation rules work for both sides. Therefore you should use Form Builder. While opening form by using Form::open you can give $rules as second parameter:
```php
    $rules = ['name' => 'required|max:100', 'email' => 'required|email', 'birthdate' => 'date'];
    Form::open(array('url' => 'foo/bar', 'method' => 'put'), $rules);
    Form::text('name');
    Form::text('email');
    Form::text('birthdate');
    Form::close(); // don't forget to close form, it reset validation rules
```
Also if you don't want to struggle with $rules at view files, you can set it in Controller or route by using Form::setValidation . This sets rules for first Form::open
```php    
    // in controller or route
    $rules = ['name' => 'required|max:100', 'email' => 'required|email', 'birthdate' => 'date'];
    Form::setValidation($rules);
    
    // in view
    Form::open(array('url' => 'foo/bar', 'method' => 'put'), $rules);
    // some form inputs
    Form::close();
```
For rules which is related to input type in laravel (such as max, min), the package looks for other given rules to understand which type is input. If you give integer or numeric as rule with max, min rules, the package assume input is numeric and convert to data-rule-max instead of data-rule-maxlength.
```php
    $rules = ['age' => 'numeric|max'];
```
The converter assume input is string by default. File type is not supported yet.

**Validation Messages**

Converter uses validation messages of laravel (app/lang/en/validation.php) by default for client-side too. If you want to use jquery validation messages, you can set useLaravelMessages, false in config file of package which you copied to your config dir. By default, it is true.

### Example
Controller/Route side
```php
class UserController extends Controller {
    
    public static $createValidation = ['name' => 'required|max:255', 'username' => 'required|regex:/^[a-z\-]*$/|max:20', 'email' => 'required|email', 'age' => 'numeric'];
    public static $createColumns = ['name', 'username', 'email', 'age'];

    public function getCreate()
    {
        Form::setValidation(static::$createValidation);
        return View::make('user.create');
    }

    public function postCreate()
    {
        $inputs = Input::only(static::$createColumns);
        $rules = static::$createValidation;

        $validator = Validator::make($inputs, $rules);

        if($validator->fails())
        {
            // actually withErrors is not really neccessary because we already show errors at client side for normal users
            return Redirect::back()->withErrors($validator);
        }

        // try to create user

        return Redirect::back()->with('success', 'User is created successfully');
    }
}
```
View side
```php
<!DOCTYPE html>
<html lang="en">
    <head>
      <meta charset="utf-8">
      <title>Laravel to Jquery Validation</title>
    </head>
    <body>
    
        {{ Form::open('url'=>'create', 'method'=>'post') }}
        {{ Form::text('name') }}
        {{ Form::text('username') }}
        {{ Form::email('email') }}
        {{ Form::number('age') }}
        {{ Form::close() }}

        <script src="{{ asset('js/jquery-1.10.2.min.js') }}"></script>
        <script src="{{ asset('js/jquery.validate.min.js') }}"></script>
        <script src="{{ asset('js/jquery.validate.laravel.js') }}"></script>
        <script type="text/javascript">
        $('form').validate_popover({

            highlight: function(element) {
              jQuery(element).closest('.form-group').removeClass('has-success').addClass('has-error');
            },
            success: function(element) {
              jQuery(element).closest('.form-group').removeClass('has-error');
            },
            events   : 'submit',
            selector : 'input[type!=submit], select, textarea',
            callback : function( elem, valid ) {
                if ( ! valid ) {
                    $( elem ).addClass('error');
                }
            }
          });
        </script>
    </body>
</html>
```

### Supported validation rules
- Required
- Email
- URL
- Integer
- Numeric
- IP
- Same
- Regex
- Alpha
- Alphanum
- Image
- Date
- Min
- Max
- Between

### Contribute
You can fork and contribute to development of the package. All pull requests is welcome.

**Setting New Convertion Rule**

The package, converts rules by using defined methods which is named like _convertRuleRuleName and converts messages by using defined methods which is named like _convertMessageRuleName. You can add new methods to convert current rules and messages. You don't have to add method for converting messages if it is working well without adding. There is getErrorMessage method to handle error messages (which contains just :attribute parameter) by default. 

Both rule and message converter method take 3 parameters which are: 

    $parsedRule: ['name' => '', 'parameters' => []] formatted array which gives name and parameters of the rule,
    $attribute: attribute name of the input, 
    $type: type of the current input. by default it is string, if rules of an input contains numeric or integer, it is set numeric.

Both rule and message converter returns attributes for the current input. For example if you are converting required rule, you should add input tag ```data-rule-requried="true"``` by returning ```['data-rule-required'=>'true']```

You can look at existed methods to understand how it works.

### Known issues
- Some rules are not supported for now

### TODO
- Test script
- Support unsupported rules

### License
Licensed under the MIT License