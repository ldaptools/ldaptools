# All Contributions Welcome

Any pull requests (bug fixes, new features, typos, etc.) are greatly appreciated!

## Guidelines

* Please limit each pull request to a single issue or feature. Breaking them into separate items make it easier to spot 
  what's changing.  

* Please write specs for your code. If you're unfamiliar with phpspec, please read through [their introduction](http://www.phpspec.net/).

* Please add any additional documentation and docblocks for your code as necessary.

* Make sure you understand and agree to the LICENSE document in this project. Your code will be released under it.

## Coding Style

This project follows the PSR-2 standards for coding style. To automagically adjust your code for PSR-2 compatibility you
can run the php-cs-fixer included with the dev requirements:

    composer install --dev
    bin/php-cs-fixer fix

## Running Your Specs

Install the dev requirements with Composer:

    composer install --dev

Then run phpspec:

    bin/phpspec run

    
