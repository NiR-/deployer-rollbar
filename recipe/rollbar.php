<?php

use Symfony\Component\Console\Input\InputOption;

option('rollbar-token', null, InputOption::VALUE_REQUIRED, 'Rollbar token to use (you can also use ROLLBAR_TOKEN env var).');
option('rollbar-env', null, InputOption::VALUE_REQUIRED, 'Rollbar environment (you can al use ROLLBAR_ENV env var). Will default to the deployr env.');

env('rollbar_token', function () {
    $rollbarToken = getenv('ROLLBAR_TOKEN');

    if ($rollbarToken === false && input()->hasOption('rollbar-token')) {
        $rollbarToken = input()->getOption('rollbar-token');
    }

    return $rollbarToken;
});
env('rollbar_env', function () {
    if (getenv('ROLLBAR_ENV') !== false) {
        return getenv('ROLLBAR_ENV');
    } elseif (input()->hasOption('rollbar-env')) {
        return input()->getOption('rollbar-env');
    }

    return env('env');
});

task('rollbar:notify', function () {
    env('rollbar_local_username', runLocally('git config author.name')->toString());
    env('rollbar_comment', run('cd {{current}} && git log -1 --pretty=format:"%s"')->toString());

    // @see https://rollbar.com/docs/deploys_other/
    $params = [
        'access_token'   => env('rollbar_token'),
        'environment'    => env('rollbar_env'),
        'revision'       => run('cd {{current}} && git rev-parse HEAD')->toString(),
        'local_username' => env('rollbar_local_username'),
        'comment'        => env('rollbar_comment'),
    ];

    $curl = run(sprintf('curl -s -XPOST --data "%s" https://api.rollbar.com/api/1/deploy/', http_build_query($params)));
    $run = json_decode($curl, true);

    if (isset($run['err'])) {
        writeln(sprintf('<error>An error occurred while notifying Rollbar ("%s").</error>', $run['message']));

        if (isVerbose()) {
            writeln(json_encode($run));
        }
    }
})->desc('Notify rollbar about the deployment');
