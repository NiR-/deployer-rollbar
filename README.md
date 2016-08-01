# Rollbar notifier for [deployer](https://github.com/deployphp/deployer)

## Install

```bash
composer require --dev nir/deployer-rollbar:dev-master
```

## Use it

In your `deploy.php`:
```php
<?php

require __DIR__.'/vendor/nir/deployer-rollbar/recipe/rollbar.php';

after('deploy', 'rollbar:notify');
```

In your `servers.yml`:
```yaml
preprod:
    # ...
    rollbar_token: # Your rollbar server token goes here
    rollbar_env: # You may want to use the same one as the one used by your Monolog logger
```

