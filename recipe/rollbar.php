<?php

use function Deployer\option;
use function Deployer\set;
use function Deployer\get;
use function Deployer\task;
use function Deployer\input;
use function Deployer\runLocally;
use function Deployer\run;
use function Deployer\writeln;
use function Deployer\isVerbose;
use Symfony\Component\Console\Input\InputOption;

option('rollbar-token', null, InputOption::VALUE_REQUIRED, 'Rollbar token to use (you can also use ROLLBAR_TOKEN env var).');
option('rollbar-env', null, InputOption::VALUE_REQUIRED, 'Rollbar environment (you can al use ROLLBAR_ENV env var). Will default to the deployr env.');

set('rollbar_token', function () {
    $rollbarToken = getenv('ROLLBAR_TOKEN');

    if ($rollbarToken === false && input()->hasOption('rollbar-token')) {
        $rollbarToken = input()->getOption('rollbar-token');
    }

    return $rollbarToken;
});
set('rollbar_env', function () {
    if (getenv('ROLLBAR_ENV') !== false) {
        return getenv('ROLLBAR_ENV');
    } elseif (input()->hasOption('rollbar-env')) {
        return input()->getOption('rollbar-env');
    }

    return get('env');
});

task('rollbar:notify', function () {
    set('rollbar_local_username', runLocally('git config user.name')->toString());
    set('rollbar_comment', run('cd {{deploy_path}}/current && git log -1 --pretty=format:"%s"')->toString());

    // @see https://rollbar.com/docs/deploys_other/
    $params = [
        'access_token'   => get('rollbar_token'),
        'environment'    => get('rollbar_env'),
        'revision'       => run('cd {{deploy_path}}/current && git rev-parse HEAD')->toString(),
        'local_username' => get('rollbar_local_username'),
        'comment'        => get('rollbar_comment'),
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
