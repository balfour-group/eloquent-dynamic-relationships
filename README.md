# balfour/eloquent-dynamic-relationships

A library for monkey patching relationships between Eloquent models at runtime.

You can use this library inside any Laravel application or stand-alone application which uses
the Eloquent ORM.

*This library is in early release and is pending unit tests.*

## Use Case

We developed this library when a need arose to bond models separated across multiple
packages (and repositories) in an internal modular system.

Let's consider a system such as the following:

* The `framework` - the brain which glues modules together.
* A `user` module - a package which allows users to login, register, etc.
* A `sms` module - a package which allows users to send text messages.

These are 3 separate packages stored in independent repositories which have the
bare minimum knowledge of each other.

* The `framework` knows about both `user` and `sms` modules.
* The `user` module has no dependencies.
* The `sms` module knows about the `user` module.

In this system, the *Message* model can define it's **many to one** relationship to the
*User* model; however the *User* model has no context of the `sms` package and is therefore
not capable of defining the inverse **one to many** relationship back.

eg: `$message->user` would succeed, but `$user->messages` or `$user->messages()->paginate()`
would cause an error.

The responbility therefore falls on the `framework` to monkey patch this inverse relationship
on the *User* model.


## Installation

```bash
composer require balfour/eloquent-dynamic-relationships
```

This package only needs to be installed in the repository (or repositories) containing models
you want to make monkey patachable.

## Usage

### Configuring Monkey Patachable Model

The model which you want to make monkey patchable needs to either extend our base model, or use
the `HasDynamicRelationships` trait.

**Method 1 (preferred)**

```php
namespace SMSModule;

use Balfour\EloquentDynamicRelationships\HasDynamicRelationships;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasDynamicRelationships;
    
    //
}
```

**Method 2**

```php
namespace SMSModule;

use Balfour\EloquentDynamicRelationships\Model;

class Message extends Model
{
    //   
}
```

### Creating Dynamic Relationship

In your framework codebase which glues everything together, you'll need to bond the two
models together.  This should typically be done at a bootstrap stage, such as when your app's
`AppServiceProvider` is booted.

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use SMSModule\Message;
use UserModule\User;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        UserModule::bond('messages', function () {
            return $this->hasMany(Message::class);
        });
    }
}
```

With the bond established between the 2 models, you can now use relationship calls as per normal.

eg:

```php
use SMSModule\Message;
use UserModule\User;

$user = User::find(1);

// retrieve a user's messages
$messages = $user->messages;

// retrieve and sort a user's messages by send date
$messages = $user->messages()
    ->latest('send_date')
    ->get();
    
// paginate a user's messages
$messages = $user->messages()
    ->paginate();

// get the user who sent the message
$message = Message::first();
$user = $message->user;
```

### Other Methods

```php
use UserModule\User;

// destroy a bond between 2 models
User::breakup('activities');

// retrieve all dynamic bonds
$bonds = User::getBonds();

// determine if a model is bonded with another
User::isBondedWith('activities');
```
